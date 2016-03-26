var Archivers = {
  load : function() {
    adminApi_get("/archivers", Archivers.statusCallback, Archivers.error);
  },
  /**
   * @param {Array} data
   */
 statusCallback : function(data) {
    var tbody = document.getElementById('archiverTable');
    if(typeof(data.error) === 'undefined') {
      tbody.innerHTML = "";
      var str = "";
      for(var i = 0; i < data.length; i++) {
        var action = "<a href='javascript:Archivers.start(&quot;"+data[i].board+"&quot;);'>Start</a>";
        if(data[i].status == "Running" || data[i].status == "Stopping") {
          action = "<a href='javascript:Archivers.stop(&quot;"+data[i].board+"&quot;);'>Stop</a>"
          +"&nbsp;<a href='javascript:Archivers.loadBuffer(&quot;"+data[i].board+"&quot;);'>Get Output</a>";
        }
        str += "<tr><td>"+data[i].board+"</td><td>"+data[i].status+"</td><td>"+action+"</td></tr>";
      }
      tbody.innerHTML = str;
    }
  },
  error : function(err, code, msg) {
    alert("Couldn't load ")
  },
  start : function(board) {
    adminApi_get("/archiver/"+board+"/start", Archivers.load, null);
  },
  stop : function(board) {
    adminApi_get("/archiver/"+board+"/stop", Archivers.load, null);
  },
  loadBuffer : function(board) {
    adminApi_get("/archiver/"+board+"/output", Archivers.bufferCallback, null);
  },
  bufferCallback : function(data) {
    if(typeof(data.output) !== 'undefined') {
      var el = document.getElementById('buffer');
      el.value = data.output;
      el.scrollTop = el.scrollHeight;
    }
  }
};

var Boards = {
  get4chan: function() {
    adminApi_get("/boards4chan", Boards.chanCallback);
  },
  chanCallback: function(data) {
    console.log(data);
  }
};

document.addEventListener("DOMContentLoaded", function(e) {
  Archivers.load();
});