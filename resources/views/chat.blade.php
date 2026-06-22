<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Velq WS Test</title></head>
<body>
<h1>Velq WS chat</h1>
<div id="status">init</div>
<ul id="messages"></ul>
<script>
function wire(){
  if(!window.Echo){ setTimeout(wire,200); return; }
  var c=window.Echo.connector.pusher.connection;
  document.getElementById('status').textContent='echo:'+c.state;
  c.bind('state_change',function(s){ document.getElementById('status').textContent='echo:'+s.current; });
  window.Echo.channel('chat').listen('.MessagePosted',function(e){
    var li=document.createElement('li'); li.textContent=e.message||JSON.stringify(e);
    document.getElementById('messages').appendChild(li);
  });
  window.__wsState=function(){ try{return window.Echo.connector.pusher.connection.state;}catch(e){return 'na';} };
}
wire();
</script>
</body></html>
