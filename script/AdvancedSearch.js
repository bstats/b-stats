var totalFilters = 0;
var totalSorts = 0;
var filters = {
               "no":"post number",
               "threadid":"thread id",
               "id":"id",
               "name":"name",
               "trip":"tripcode",
               "nametrip":"name+tripcode",
               "subject":"subject",
               "email":"email",
               "time":"timestamp",
               "filesize":"filesize (bytes)",
               "filename":"filename",
               "extension":"extension",
               "capcode":"capcode",
               "comment":"comment (raw, w/html)",
               "md5":"md5",
               "deleted":"deleted (1 or 0)"
              };
var operators = {"=":"equals (=)",
                "LIKE":"contains (*)",
                "&lt;&gt;":"doesn't equal (!=)",
                "NOT LIKE":"doesn't contain (!*)",
                "&gt;":"greater than (&gt;)",
                "&lt;":"less than (&lt;)",
                "STARTS":"starts with (^)",
                "ENDS":"ends with ($)"};
var options = "";
    $.each(filters,function(key, val){
        options += "<option value='"+key+"'>"+val+"</option>";
    });
var ops = "";
    $.each(operators,function(key, val){
        ops += "<option value='"+key+"'>"+val+"</option>";
    });
$("#preserveSearch").click(function(){
   if(this.checked){
       $("#advsearchform").attr("method","get");
   }
   else{
       $("#advsearchform").attr("method","post");
   }
});
var checkBoards = function(e){
    var selector = $("select[name='board']");
    if(selector.val() !== "b"){
        $("#threadType").css("display","none");
        $("select[name=threadtype]").val("all");
        $("option[value=id]").css("display","none");
    }
    else{
        $("#threadType").css("display","");
        $("option[value=id]").css("display","");
    }
    if(selector.val() === "f"){
        if($("select[name=srchtype]").val() === 'images')
            $("select[name=srchtype]").val('posts');
        $("option[value=images]").css("display","none");
        $("span#flashType").css("display","");
    }
    else{
        $("option[value=images]").css("display","");
        $("span#flashType").css("display","none");
        $("select[name=flashtype]").val('all');
    }
};
var checkFilters = function(){
    $("select.selectFilter").each(function(){
        var no = this.name.replace(/filter\[([0-9]+)\]/,'$1');
        var op = $("select[name='operator["+no+"]']");
        
        //comments are speshul
        if(this.value === 'comment'){
            op.children().css("display","none");
            op.children("[value=LIKE]").css("display","");
            op.val("LIKE");
        }
        //values which would be silly to search for anything but the whole thing
        else if(this.value === 'md5' || this.value === 'deleted' || this.value === 'extension' || this.value === 'id' || this.value === 'capcode'){
            op.children().css("display","none");
            op.children("[value='=']").css("display","");
            op.val("=");
        }
        //numerical values
        else if(this.value === 'time' || this.value === 'no' || this.value === 'threadid' || this.value === 'filesize'){
            op.children().css("display","none");
            op.children("[value='=']").css("display","");
            op.children("[value='<']").css("display","");
            op.children("[value='>']").css("display","");
            op.children("[value='<>']").css("display","");
            var v = op.val();
            if(v !== '=' && v !== '<' && v !== '>' && v !== '<>') op.val("=");
        }
        //string value
        else if(this.value === 'name' || this.value === 'trip' || 
                this.value === 'nametrip' || this.value === 'subject' || 
                this.value === 'email' || this.value === 'filename'){
            op.children().css("display","none");
            op.children("[value='=']").css("display","");
            op.children("[value='LIKE']").css("display","");
            op.children("[value='NOT LIKE']").css("display","");
            op.children("[value='STARTS']").css("display","");
            op.children("[value='ENDS']").css("display","");
            var v = op.val();
            if(v !== '=' && v !== 'LIKE' && v !== 'NOT LIKE' && v !== 'STARTS' && v !== 'ENDS') op.val("=");
        }
        else {
            op.children().css("display","");
        }
    });
};

$("#boardSelector").change(checkBoards);

$("#addFilter").click(function(){
    $("#filters").append($("<span id='filter"+totalFilters+"'></span>"));
    $("#filter"+totalFilters).append($("<select class='selectFilter' name='filter["+totalFilters+"]'>"+options+"</select>").on("change",checkFilters));
    $("#filter"+totalFilters).append($("<select name='operator["+totalFilters+"]'>"+ops+"</select>"));
    $("#filter"+totalFilters).append($("<input type='text' name='value["+totalFilters+"]'><br>"));
    checkFilters();
    totalFilters++;
});
$("#removeFilter").click(function(){
    if(totalFilters === 0) return;
    $("#filter"+(totalFilters-1)).remove();
    console.log("Removed #filter"+(totalFilters-1));
    totalFilters--;
});
$("#removeSort").click(function(){
    if(totalSorts === 1) return;
    $("#sort"+(totalSorts-1)).remove();
    console.log("Removed #filter"+(totalSorts-1));
    totalSorts--;
});
$("#addSort").click(function(){
    $("#sorts").append($("<span id='sort"+totalSorts+"'></span>"));
    $("#sort"+totalSorts).append($("<select name='sort["+totalSorts+"]'>"+options+"</select>"));
    $("#sort"+totalSorts).append($("<select name='order["+totalSorts+"]'><option value='asc'>ascending</option><option value='desc'>descending</option></select><br>"))
    totalSorts++;
});