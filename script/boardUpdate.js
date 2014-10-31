/*
 * keep those board update times up-to-date
 */
setInterval(function(){
    $.ajax({
        dataType: "json",
        headers: {"X-Requested-With":"Ajax"},
        url: protocol+'//'+host+'/api.php',
        type: "POST",
        data: "a=allBoardsInfo"
    }).success(function(data){
        $.each($(".ago"),function(id,el){
            $(el).attr("data-utc",data[$(el).attr("data-board")]['last_crawl']);
        });
    });
},10000);