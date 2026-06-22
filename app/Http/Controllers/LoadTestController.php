<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Epic 10 load-test harness ONLY.
 *
 * Two deliberately heavy endpoints used by crossbox/loadtest-quota-cgroup.sh
 * to drive real CPU and disk-IO pressure against a deployed DO, so the per-DO
 * cgroup v2 caps (-cpu-max-per-do / -io-max-bps-per-do) can be PROVEN to throttle
 * on the box. They have NO place in a real app and are gated behind clearly-named
 * /loadtest/* routes. Both are SELF-BOUNDED (the caller passes an explicit budget,
 * capped here) so they always stop regardless of any throttle: the worst case of a
 * present-but-broken cap is a SLOW completion, never an unbounded burn/write.
 *
 * Pure PHP, zero dependencies. Nothing here touches the DB, Files, KV or queue.
 */
class LoadTestController extends Controller
{
    /**
     * Cap on the CPU burn budget (ms). At 1 vCPU this is at most ~60s of spin.
     */
    private const MAX_BURN_MS = 60000;

    /**
     * Cap on the IO write budget (MB). Bounds the temp file so a missing IO cap
     * cannot fill the data-disk: it writes at most this many MB then deletes it.
     */
    private const MAX_IO_MB = 600;

    /**
     * Default IO write budget (MB) when ?mb= is omitted.
     */
    private const DEFAULT_IO_MB = 500;

    /**
     * GET /loadtest/burn?ms=N
     *
     * Busy-loop burning CPU for N milliseconds (pure PHP microtime spin, no sleep).
     * N is clamped to [0, MAX_BURN_MS] so the burn is always self-bounded. With a
     * per-DO cpu.max cap the kernel CFS-throttles this spin; without one it pins a
     * core for the full duration. Either way it returns "burned N" when N ms of
     * WALL-CLOCK have elapsed.
     */
    public function burn(Request $request): Response
    {
        $ms = (int) $request->query('ms', '1000');
        if ($ms < 0) {
            $ms = 0;
        }
        if ($ms > self::MAX_BURN_MS) {
            $ms = self::MAX_BURN_MS;
        }

        // Wall-clock deadline. We keep the CPU 100% busy doing throwaway integer
        // math (no sleep, no IO) until the deadline passes. Under a cpu.max cap the
        // process is throttled, so the SAME wall-clock target takes more real CPU
        // time slices to reach -> nr_throttled / throttled_usec climb in cpu.stat.
        $deadline = microtime(true) + ($ms / 1000.0);
        $x = 0;
        while (microtime(true) < $deadline) {
            // A tight inner loop between clock reads so the spin actually saturates
            // the CPU instead of spending all its time in microtime().
            for ($i = 0; $i < 100000; $i++) {
                $x += $i ^ ($i << 1);
            }
        }

        return response("burned {$ms}\n", 200)
            ->header('X-Loadtest-Sink', (string) $x)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * GET /loadtest/iowrite?mb=M
     *
     * Write a FIXED M megabytes to a temp file ON THE DATA-DISK (/data, the device
     * that backs the per-DO sqlite and that io.max throttles), in 1 MiB chunks with
     * fflush()+fsync() after EACH chunk (so the bytes hit the block device and are
     * subject to io.max, not just buffered in page-cache), then DELETE the temp file
     * and return "wrote M MB".
     *
     * SELF-BOUNDED by M (clamped to [1, MAX_IO_MB]): it writes exactly M MiB and
     * stops, regardless of any throttle. With a per-DO io.max wbps cap the write is
     * blkio-throttled to that bandwidth (M MiB takes ~M/throttle seconds); without a
     * cap it runs at the disk's native rate. Worst case of a broken cap is a slow
     * write, never an unbounded one.
     */
    public function iowrite(Request $request): Response
    {
        $mb = (int) $request->query('mb', (string) self::DEFAULT_IO_MB);
        if ($mb < 1) {
            $mb = 1;
        }
        if ($mb > self::MAX_IO_MB) {
            $mb = self::MAX_IO_MB;
        }

        // Write ON THE DATA-DISK. /data is where the per-DO data-disk is mounted in
        // the guest (/data/database.sqlite) - the exact device io.max throttles.
        // HARD-FAIL (500) if /data is not writable rather than silently falling back
        // to storage_path(): a fallback to the RO-rootfs overlay/tmpfs would be an
        // UNTHROTTLED device, making the harness's IO timing silently wrong. An
        // unambiguous 500 is far better than a fast write on the wrong disk.
        if (! is_dir('/data') || ! is_writable('/data')) {
            return response("iowrite: /data not writable (cannot measure io.max on the data-disk)\n", 500)
                ->header('Content-Type', 'text/plain');
        }

        $path = '/data/velq-loadtest-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.tmp';

        $oneMiB = str_repeat("\0", 1024 * 1024);
        $fh = @fopen($path, 'wb');
        if ($fh === false) {
            return response("iowrite: cannot open {$path}\n", 500)
                ->header('Content-Type', 'text/plain');
        }

        try {
            for ($i = 0; $i < $mb; $i++) {
                $written = fwrite($fh, $oneMiB);
                if ($written === false) {
                    throw new \RuntimeException("fwrite failed at chunk {$i}");
                }
                // Force the chunk out of the userspace+kernel buffers onto the block
                // device so io.max (blkio) actually sees the write bandwidth.
                fflush($fh);
                if (function_exists('fsync')) {
                    @fsync($fh);
                }
            }
        } catch (\Throwable $e) {
            fclose($fh);
            @unlink($path);

            return response('iowrite: ' . $e->getMessage() . "\n", 500)
                ->header('Content-Type', 'text/plain');
        }

        fclose($fh);
        // ALWAYS delete the temp file: the write is the test, the bytes are garbage.
        @unlink($path);

        return response("wrote {$mb} MB\n", 200)
            ->header('Content-Type', 'text/plain');
    }
}
