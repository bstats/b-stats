/**
 * Call the admin API using GET
 * 
 * @param {String} endpoint the endpoint, like "/archivers" or "/archivers/y/start"
 * @param {Function} callback for returned data
 * @returns {undefined}
 */
var adminApi_get = function(endpoint, callback, error) {
  $.ajax({
    dataType: "json",
    headers: {"X-Requested-With":"Ajax"},
    url: protocol+'//'+host+'/admin'+endpoint,
    type: "GET",
    error: error,
    success: callback
  });
};

/**
 * Call the admin API using POST
 * 
 * @param {String} endpoint the endpoint, like "/archivers" or "/archivers/y/start"
 * @param {String|Array} data
 * @param {Function} callback for returned data
 * @returns {undefined}
 */
var adminApi_post = function(endpoint, data, callback, error) {
  $.ajax({
    dataType: "json",
    headers: {"X-Requested-With":"Ajax"},
    url: protocol+'//'+host+'/admin'+endpoint,
    type: "POST",
    error: error,
    success: callback,
    data: data
  });
};

var deleteReport = function(no,board){
    if(confirm("Really delete the report for "+no+"?")){
        adminApi_post('/deleteReport/'+board+'/'+no, '',
            function(data){
                if(data.err === true){
                    alert("Error: "+data.errmsg);
                }
                else{
                    removeRow("#report"+no);
                }
            },
            function(e){
                alert('Unsuccessful');
            });
    }
};

var banImage = function(hash){
    if(confirm("Really ban (and delete) the image "+hash+"?")){
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/adminAPI.php',
                type: "POST",
                data: "a=banImage&hash="+hash }).
        success(function(data){
            if(data.err === true){
                alert("Error: "+data.errmsg);
            }
            else{
                $('#ban'+hash).remove();
            }
        });
    }
};

var deletePost = function(no,board){
    if(confirm("Really delete post #"+no+"?")){
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/adminAPI.php',
                type: "POST",
                data: "a=deletePost&no="+no+"&b="+board  }).
        success(function(data){
            if(data.err === true){
                alert("Error: "+data.errmsg);
            }
            else{
                removeRow("#report"+no);
            }
        });
    }
};

var banReporter = function(no,board){
    if(confirm("Really ban the reporter of "+no+"?")){
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/adminAPI.php',
                type: "POST",
                data: "a=banReporter&no="+no+"&b="+board }).
        success(function(data){
            if(data.err === true){
                alert("Error: "+data.errmsg);
            }
            else{
                removeRow("#report"+no);
            }
        });
    }
};

var restorePost = function(no,board){
    if(confirm("Really restore post #"+no+"?")){
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/adminAPI.php',
                type: "POST",
                data: "a=restorePost&no="+no+"&b="+board }).
        success(function(data){
            if(data.err === true){
                alert("Error: "+data.errmsg);
            }
            else{
                removeRow("#report"+no);
            }
        });
    }
};

var removeRow = function(selector){
    $(selector).find('td').animate({padding:0},{duration:150,easing:"linear"}).wrapInner('<div style="display: block;margin:0;padding:0;" />')
       .parent().find('td > div').slideUp(150, function(){$(this).parent().parent().remove();});
};

$(document).ready(function(){
    var stats = $("#threadStats");
    if(stats != null){
        //stats.append("<table class='borderless' style='width:30em'><tr><th>Admin Tools:</th><td><a id='adm_fixdel' href='javascript:;' onclick='javascript:fixDeleted("+stats.attr("data-thread")+");'>[Deleted] Fix</a></td></tr></table>");
    }
});