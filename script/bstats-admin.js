var deleteReport = function(no,board){
    if(confirm("Really delete the report for "+no+"?")){
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/adminAPI.php',
                type: "POST",
                data: "a=deleteReport&no="+no+"&b="+board  }).
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

var fixDeleted = function(threadid){
    if(confirm("Remove all [Deleted] marks? Warning: This is IRREVERSIBLE")){
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/adminAPI.php',
                type: "POST",
                data: "a=fixDeleted&t="+threadid }).
        success(function(data){
            if(data.err === true){
                alert("Error: "+data.errmsg);
            }
            else{
                $("#adm_fixdel").replaceWith('<a style="opacity:0.5;">[Deleted] Fix</span>');
                $(".warning").remove();
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
        stats.append("<table class='borderless' style='width:30em'><tr><th>Admin Tools:</th><td><a id='adm_fixdel' href='javascript:;' onclick='javascript:fixDeleted("+stats.attr("data-thread")+");'>[Deleted] Fix</a></td></tr></table>");
    }
});