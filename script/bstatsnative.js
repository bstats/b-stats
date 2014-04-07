var protocol = window.location.protocol;
var path = location.pathname.split("/res/");
location.hash = location.hash.replace(/#([0-9]{1,9})/,'#p$1');
if(/\/res\//.test(location.pathname) )
    if(location.pathname.substr(location.pathname.length - 1) === "/")
        window.location.href = window.location.protocol + "//" + location.host + location.pathname.substr(0,location.pathname.length - 1) + location.hash;

var thread = path[1];
var apiPosts = new Array();
var host = location.host;
var hoverLoaded = false;

offsetX = 50;
offsetY = 150;

var hoverOn = function(e){
    if($(this).hasClass("inlined")) return;
    var postId = $(this).attr("data-post");
    if(!postId) postId = $(this).children("span.linkpart").text();
    if(!postId) return;
    $("body").append("<div id='hover' class='reply'></div>");
    $("#hover").css("top",(e.clientY - offsetY) + "px").css("left",(e.clientX + offsetX) + "px").fadeIn("slow");
    if($("#pc"+postId).length){
        $("#hover").append($("#pc"+postId).clone());
        $("#hover .post").css("margin","0px").css("border","0px");
        loadThumb("#hover");
        hoverLoaded = true;
    }
    else{
        $("#hover").css("padding","3px").html("Loading post No."+postId+"...");
        loadExtern(postId,$(this).attr("data-board"));
    }
};

var hoverMove = function(e){
        if($(this).hasClass("inlined")){ 
            $("#hover").remove();
            hoverLoaded = false;
            return;
        }
        Y = e.clientY - offsetY;
        if(e.clientX > window.innerWidth/1.5)
            flip = -1;
        else
            flip = 1;
        if(e.clientY < offsetY + ($(".navbar").height() + 8))
            Y = ($(".navbar").height() + 8);
        if(e.clientY > (window.innerHeight - ($("#hover").height()-offsetY)))
            Y = window.innerHeight - $("#hover").height();
        if(flip == 1)
            $("#hover").css("left",(e.clientX + offsetX) + "px");
        else
            $("#hover").css("left",(e.clientX - offsetX - $("#hover").width()) + "px");
        $("#hover").css("top",Y + "px").css("margin-right","5px" ).css("max-width",((window.innerWidth - flip*e.clientX) - (flip)*(offsetX + 20))+"px");
};

var preview = function(inline){
    flip = 1;
    $(inline + " a.backlink, "+inline+" a.quotelink").each(function(index,element){
        $(element).html($(element).html().replace(/&gt;&gt;([0-9]+)/,'&gt;&gt;<span class="linkpart">$1</span>'));
    }).hover(function(e){
        hoverOn.call(this,e);
    },function(){
        $("#hover").remove();
        hoverLoaded = false;
    }).mousemove(function(e){
        hoverMove.call(this,e);
    });
    $(inline + " a.backlink .linkpart, "+inline+" a.quotelink:not(.noEmbed) .linkpart").click(function(e){
        if(e.which === 1){ //If it's a left click
            e.preventDefault();
            embedPost($(this).parent());
        }
    });
    /**/
};

function expandImgLinks(){
    $(".imageLink").each(function(index){
        text = $(this).html();
       if(text.length > 25)
           $(this).html(text.substring(0,20)+"(...)"+text.substring(text.length-4));
    });
    $("a.imageLink").hover(function(e){
        this.t = this.title;
        this.title = "";
        temp = this.t;
        this.t = $(this).text();
        $(this).text(temp);
    },function(){
        temp = $(this).text();
        this.title = temp;
        $(this).text(this.t);
    });
}

function loadExtern(postID,board){
    if(typeof apiPosts[postID] === 'undefined'){
        board = board ? board : 'b';
        $.ajax({
            dataType: "html",
            headers: {"X-Requested-With":"Ajax"},
            url: protocol+'//'+host+'/api.php',
            type: "POST",
            data: "a=post&id="+postID+"&b="+board
        }).success(function(data){
            apiPosts[postID] = data;
            $("#hover").html(data);
            $("#hover .post").css("margin","0px").css("border","0px");
            preview("#hover");
            fixAllCrossLinks("#hover");
            loadThumb("#hover");
            hoverLoaded = true;
        });
    }
    else{
        $("#hover").html(apiPosts[postID]);
        $("#hover .post").css("margin","0px").css("border","0px");
        hoverLoaded = true;
    }
}
function fixAllCrossLinks(container){
    $(container + " .quotelink").each(function(index){
        var link = this.href;
        var board = $(this).attr("data-board");
        var link_thread = $(this).attr("data-thread");
        var post = $(this).attr("data-post");
        if(link_thread != thread && link_thread != 0 
                && /\/res\//.test(document.location.href) 
                && !/\/post\//.test(this.href)
                && !/Cross-thread/.test(this.innerText)
        ){
            $(this).html($(this).html() + " (Cross-thread)");
        }
        if($(this).attr("data-thread")){
            $(this).attr("href","/"+board+"/res/"+link_thread+"#p"+post);
        }
    });
    $(container + " .deadlink").each(function(index){
        var id = this.innerHTML.substring(8);
        var myBoard = $(this).attr("data-board"); //works 99% of the time
        $(this).replaceWith($("<a href='../post/"+id+"#p"+id+"' data-board='"+myBoard+"' data-post='"+id+"' class='quotelink'>"+this.innerHTML+" (Dead)</a>"));
    });
}

function embedPost(link) {
    var linkedPost = $(link).children("span.linkpart").text();
    if($(link).hasClass("inlined") === true){
        $("#embed"+linkedPost).remove();
        $(link).removeClass("inlined");
    }
    else {
        if(hoverLoaded){
            var insertAfter;
            if($(link).hasClass("backlink")){
                insertAfter = $(link).parent().parent();
            }
            else if($(link).parent().hasClass("quote")){
                insertAfter = $(link).parent(); // /f/ is messed up and puts <span class="quote"> around links...
            }
            else{
                insertAfter = $(link);
            }
            var embedded = $("<div class='inline'></div>").attr("id","embed"+linkedPost)
                    .insertAfter(insertAfter);
            
            $("#embed"+linkedPost).html($("#hover").html());
            $(link).addClass("inlined");
            preview("#embed"+linkedPost);
            ImageHover.init("#embed"+linkedPost);
        }
    }
}

var ExpandImage = {
    init: function(){
        $("a.fileThumb").on("click",function(e){
            if(e.which === 1){ //If it's a left click
                e.preventDefault();
                var thumb = $(this).children("img")[0];
                if($(thumb).hasClass("expand-loading")){
                    return;
                }
                if($(thumb).hasClass("expand-loaded")){
                    ExpandImage.shrink($(thumb).next());
                    return;
                }
                ExpandImage.expand(thumb);
            }
        });
    },
    expand: function(thumb){
        if($(thumb).hasClass("expand-loading") || $(thumb).hasClass("expand-loaded"))
            return;
        $(thumb).addClass("expand-loading");
        var md5 = $(thumb).attr("data-md5-filename");
        var ext = $(thumb).attr("data-ext");
        var width = $(thumb).attr("data-width");
        var height = $(thumb).attr("data-height");
        if(ext != ".webm"){
            var newImg = $("<img class='expanded' style='display:none' alt='image'/>");
            newImg.attr("data-width",width).attr("data-height",height);
            newImg.load(function(){ExpandImage.loaded(this);});
            $(thumb).after(newImg);             
            newImg.attr("src","https://images.b-stats.org/"+md5+ext);
        }
        else {
            var newImg = $("<video id='"+md5+"' class='expanded' style='display:block' alt='video' loop autoplay controls />");
            newImg.attr("data-width",width).attr("data-height",height);
            newImg.attr("src","https://images.b-stats.org/"+md5+ext+"#t="+document.getElementById("hoverImg").currentTime);
            $(thumb).removeClass("expand-loading").addClass("expand-loaded").after(newImg)
            $("#hoverImg").remove();
            ImageHover.checkScale(newImg);
        }
    },
    loaded: function(img){
        
        $(img).prev().removeClass("expand-loading").addClass("expand-loaded");
        $(img).css("display","block");
        $("#hoverImg").remove();
        ImageHover.checkScale(img);
    },
    shrink: function(img){
        $(img).prev().removeClass("expand-loaded").attr("src",img.src).attr("data-original",img.src);
        $(img).remove();
    },
    expandAll: function(){
        $(".fileThumb").each(function(i,e){
            ExpandImage.expand($(e).children("img")[0]);
        });
    },
    shrinkAll: function(){
        $(".expanded").each(function(i,e){
            ExpandImage.shrink(e);
        });
    }
};

var ImageHover = {
    init : function(inline){
        //return; //temp
        $(inline+" a.fileThumb img:nth-of-type(1)").
            hover(ImageHover.hover, function(){$("#hoverImg").remove();}).
            mousemove(ImageHover.mouseMove);
    },
    hover : function(e){
        var thumb = $(this);
        var md5 = thumb.attr("data-md5-filename");
        var ext = thumb.attr("data-ext");
        var width = thumb.attr("data-width");
        var height = thumb.attr("data-height");
        if(ext !== ".webm"){
            $("body").append("<img id='hoverImg' style='visibility:hidden;' alt=''/>");
            $("#hoverImg").attr("data-width",width);
            $("#hoverImg").attr("data-height",height);
            $("#hoverImg").css("position","absolute").css("top","0px").css("left","0px").css("position","fixed").fadeIn("slow");
            ImageHover.checkScale(document.getElementById("hoverImg"));
            $("#hoverImg").on("load",function(){
                thumb.attr("src","//images.b-stats.org/"+md5+ext);
                thumb.mousemove();
                $("#hoverImg").css("visibility","visible");
            });
            $("#hoverImg").attr("src","//images.b-stats.org/"+md5+ext);
        }
        else{
            $("body").append("<video id='hoverImg' autoplay loop alt=''/>");
            $("#hoverImg").attr("data-width",width).attr("data-height",height)
                .css("top","0px").css("left","0px").css("position","fixed")
                .attr("src","//images.b-stats.org/"+md5+ext);
            ImageHover.checkScale(document.getElementById("hoverImg"));
            thumb.mousemove();
        }
    },
    mouseMove : function(e){
        var Y = e.clientY - offsetY;
        var hover = $("#hoverImg");
        var navHeight = $(".navbar").height();
        var hoverRatio = hover.attr("data-width") / hover.attr("data-height");
        if(e.clientX > window.innerWidth/1.5)
            flip = -1;
        else
            flip = 1;
        if(Y < navHeight + 4)
            Y = (navHeight + 4);
        if(Y + hover.height() > window.innerHeight - 12)
            Y = window.innerHeight - hover.height() - 12;
        if(flip === 1){
            hover.css("left",(e.clientX + offsetX) + "px");
            ImageHover.checkScale(document.getElementById("hoverImg"));
        }
        else{
            hover.css("left",(e.clientX - offsetX - hover.width()) + "px");
            if(e.clientX - offsetX - hover.width() < 0){
                hover.width(e.clientX - offsetX);
                hover.height(hover.width() * (1/hoverRatio));
            }
        }
        hover.css("top",Y + "px").css("margin-right","5px");
        
    },
    revertAll : function(){
        $(" a.fileThumb img:nth-of-type(1)").each(function(){
            var thumb = $(this);
            var md5 = thumb.attr("data-md5-filename");
            var ext = thumb.attr("data-ext");
            thumb.attr("src","//thumbs.b-stats.org/"+md5+".jpg");
        });
    },
    checkScale : function(hoverImg){
        var width = $(hoverImg).attr("data-width");
        var pos = $(hoverImg).offset();

        var imgRatio = $(hoverImg).attr("data-width") / $(hoverImg).attr("data-height");
        var screenRatio = (window.innerWidth - 12 - pos.left) / (window.innerHeight - ($(".navbar").height() + 20));
        if(width > (window.innerWidth - 12 - pos.left) || $(hoverImg).attr("data-height") > (window.innerHeight - ($(".navbar").height() + 20))){
            if(imgRatio > screenRatio){
                hoverImg.width = (window.innerWidth - 12 - pos.left);
                hoverImg.height = (1/imgRatio)*(window.innerWidth - pos.left);
            }
            if(imgRatio < screenRatio){
                hoverImg.height = (window.innerHeight - ($(".navbar").height() + 20));
                hoverImg.width = imgRatio*(window.innerHeight - ($(".navbar").height() + 20));
            }
        }
        else {
            hoverImg.width = $(hoverImg).attr("data-width");
            hoverImg.height = $(hoverImg).attr("data-height");
        }
    }
};

var StyleSwitcher = {
    currentStyle : "",
    init : function() {
        if(/yotsuba/.test(document.getElementsByTagName("head")[0].innerHtml()))
            this.currentStyle = "light";
        else
            this.currentStyle = "dark";
    },
    switchTo : function(style){
        if(style==="light"){
            document.getElementById('chanCSS').href = "/css/yotsuba.css";
            document.getElementById('statsCSS').href = "/css/bstats-yotsuba.css";
            $.ajax({
                dataType: "html",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/style.php',
                type: "GET",
                data: "style=yotsuba"
            });
        }
        else if(style==="blue"){
            document.getElementById('chanCSS').href = "/css/yotsuba-blue.css";
            document.getElementById('statsCSS').href = "/css/bstats-yotsuba-blue.css";
            $.ajax({
                dataType: "html",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/style.php',
                type: "GET",
                data: "style=yotsuba-blue"
            });
        }
        else if(style === "dark"){
            document.getElementById('chanCSS').href = "/css/tomorrow.css";
            document.getElementById('statsCSS').href = "/css/bstats-tomorrow.css";
            $.ajax({
                dataType: "html",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/style.php',
                type: "GET",
                data: "style=tomorrow"
            });
        }
        else if (style === "pink"){
            document.getElementById('chanCSS').href = "/css/yotsuba-pink.css";
            document.getElementById('statsCSS').href = "/css/bstats-yotsuba-pink.css";
            $.ajax({
                dataType: "html",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/style.php',
                type: "GET",
                data: "style=yotsuba-pink"
            });
        }
    }
};

function initAdditions(){
    if(/\/(b|hm|f)\//.test(location.pathname)){
        $("#topLinks").html($("#topLinks").html() + 
          //" [<a href='javascript:;' onclick='ExpandImage.expandAll();'>Expand Images</a>]"+
          " [<a href='javascript:;' onclick='ExpandImage.shrinkAll();'>Shrink Images</a>]"+
          " [<a href='javascript:;' onclick='ImageHover.revertAll();'>Reset Thumbs</a>]");
    }
};

var loadThumb = function(selector){
    var selected = $(selector + " img.lazyload");
    selected.attr("src",selected.attr("data-original"));
};

var reportPost = function(link,board,post,thread){
    if(confirm("Really report post #"+post+"? \nFrivolous reporters will be BANNED from the archives.")){
        link.onclick="";
        $(link).text("Reported!").css("opacity",0.5).css("cursor","normal").addClass("clicked");
        $.ajax({
                dataType: "json",
                headers: {"X-Requested-With":"Ajax"},
                url: protocol+'//'+host+'/report.php',
                type: "POST",
                data: "b="+board+"&p="+post+"&t="+thread
               });
    }
};

$(document).ready(function(){
    fixAllCrossLinks('');
    preview('');
    expandImgLinks();
    ExpandImage.init();
    initAdditions();
    $("img.lazyload").lazyload();
});