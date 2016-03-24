/*
 * keep those board update times up-to-date
 */
setInterval(function(){
    $.ajax({
        dataType: "json",
        headers: {"X-Requested-With":"Ajax"},
        url: protocol+'//'+host+'/api/boards',
        type: "GET"
    }).success(function(data){
        $.each($(".ago"),function(id,el){
            $(el).attr("data-utc",data[$(el).attr("data-board")]['last_crawl']);
        });
        $.each($(".threadcount"),function(id,el){
          $(el).text(data[$(el).attr("data-board")]['threads']);
        });
        $.each($(".postcount"),function(id,el){
          $(el).text(data[$(el).attr("data-board")]['posts']);
        });
    });
},10000);