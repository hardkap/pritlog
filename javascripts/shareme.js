/* bPath="http://hardkap.net/blog/bookmarks"; */ /* Give the path to your social bookmark images folder */
/*bPath="http://hardkap.net/blog/bookmarks";*/
/*u1=encodeURIComponent(document.location.href);*/
//t1=document.title;
items=[
	["Delicious", "javascript:location.href='http://del.icio.us/post?v=2&url="+u1+"&title="+t1+"'", 0,bPath+"/delicious.gif"],
	["Google", "javascript:location.href='http://www.google.com/bookmarks/mark?op=edit&bkmk="+u1+"&title="+t1+"'", 0,bPath+"/google.gif"],
	["Digg", "javascript:location.href='http://digg.com/submit?phase=2&url="+u1+"'", 0,bPath+"/digg.gif"],
	["Furl", "javascript:location.href='http://www.furl.net/storeIt.jsp?u="+u1+"&t="+t1+"'", 0,bPath+"/furl.gif"],
	["Live", "javascript:location.href='https://favorites.live.com/quickadd.aspx?url="+u1+"&title="+t1+"'", 0,bPath+"/windows.gif"],
	["Netscape", "javascript:location.href='http://www.netscape.com/submit/?U="+u1+"&T="+t1+"'", "efc", bPath+"/netscape.gif"],
	["Facebook", "javascript:location.href='http://www.facebook.com/sharer.php?u="+u1+"&t="+t1+"'", 0,bPath+"/facebook.gif"],
	["Ask", "javascript:location.href='http://myjeeves.ask.com/mysearch/BookmarkIt?v=1.2&t=webpages&url="+u1+"&title="+t1+"'", 0,bPath+"/ask.gif"],
	["Reddit", "javascript:location.href='http://www.reddit.com/submit?url="+u1+"&title="+t1+"'", 0,bPath+"/reddit.gif"],
	["Slashdot", "javascript:location.href='http://slashdot.org/bookmark.pl?url="+u1+"&title="+t1+"'", 0,bPath+"/slashdot.gif"],
	["Squidoo", "javascript:location.href='http://www.squidoo.com/lensmaster/bookmark?"+u1+"'", 0,bPath+"/squidoo.gif"],
	["Stumbleupon", "javascript:location.href='http://www.stumbleupon.com/submit?url="+u1+"&title="+t1+"'", "efc", bPath+"/stumble.gif"],
	["Technorati", "javascript:location.href='http://www.technorati.com/faves?add="+u1+"'", 0,bPath+"/technorati.gif"],
	["Yahoo", "javascript:location.href='http://myweb2.search.yahoo.com/myresults/bookmarklet?u="+u1+"&t="+t1+"'", 0,bPath+"/yahoo.gif"],
	["Blinklist", "javascript:location.href='http://blinklist.com/index.php?Action=Blink/addblink.php&Url="+u1+"&Title="+t1+"'", 0,bPath+"/blinklist.gif"],
	["Dzone", "javascript:location.href='http://www.dzone.com/links/add.html?url="+u1+"&title="+t1+"'", 0,bPath+"/dzone.gif"],
	["Spurl", "javascript:location.href='http://www.spurl.net/spurl.php?url="+u1+"&title="+t1+"'", 0,bPath+"/spurl.gif"],
	["Diigo", "javascript:location.href='http://www.diigo.com/post?url="+u1+"&title="+t1+"'", 0,bPath+"/diigo.gif"]
]

function findPos(obj) {
	var curleft = curtop = 0;
        if (obj.offsetParent) {
          do {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
	  } while (obj = obj.offsetParent);
          }
        return [curleft,curtop];
}
function pageWidth() {return window.innerWidth != null? window.innerWidth: document.body != null? document.documentElement.clientWidth:null;}
function pageHeight() {return window.innerHeight != null? window.innerHeight: document.body != null? document.documentElement.clientHeight:null;}
function scrollX() {return window.pageXOffset ? window.pageXOffset : document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft;}
function scrollY() {return window.pageYOffset ? window.pageYOffset : document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop;}
function shareOver() {
    Xpage=pageWidth()+scrollX();
    Ypage=pageHeight()+scrollY();
    pos=findPos(document.getElementById("shareButton"));
    x1=pos[0]+200;
    y1=pos[1]+250;
    testing1 ="x1="+x1+" Xpage="+Xpage+" pageWidth ="+pageWidth() +" scrollX="+scrollX()+"<br>";
    testing1+="y1="+y1+" Ypage="+Ypage+" pageHeight="+pageHeight()+" scrollY="+scrollY();
    //document.getElementById("testing").innerHTML=testing1;
    if (Ypage < y1) {y11=pos[1]-200;}
    else {y11=pos[1]+15;}
    if (Xpage < x1) {x11=pos[0]-190;}
    else {x11=pos[0]-0;}
    testing1 =x11+"px , "+y11+"px<br>";
    testing1+=(x11+9)+"px , "+(y11+9)+"px<br>";
    //document.getElementById("testing").innerHTML=testing1;
    document.getElementById("shareDrop").style.top =y11+"px";
    document.getElementById("shareDrop").style.left=x11+"px";
    document.getElementById("shareDrop").style.display = 'inline';
    //setMyOpacity(document.getElementById("shareDrop"),.9);
    //document.getElementById("shareshadow").style.top = (x11+9)+"px";
    //document.getElementById("shareshadow").style.left= (y11+9)+"px";
    //document.getElementById("shareshadow").style.height="150px";
    //document.getElementById("shareshadow").style.width ="350px";
    //document.getElementById("shareshadow").style.visibility = 'visible';
    //setMyOpacity(document.getElementById("shareshadow"),.4);
}
function shareOut() {
    document.getElementById("shareDrop").style.left="-900px";
    document.getElementById("shareDrop").style.top ="0px";
    document.getElementById("shareDrop").style.display = 'none';
    //setMyOpacity(document.getElementById("shareshadow"),0);
    //document.getElementById("shareshadow").style.visibility = 'hidden';
    //document.getElementById("shareDrop").style.visibility = 'hidden';
}
function setMyOpacity(el, value){
	el.style.opacity=value
	if (typeof el.style.opacity!="string"){ //if it's not a string (ie: number instead), it means property not supported
		el.style.MozOpacity=value
		if (el.filters){
			el.style.filter="progid:DXImageTransform.Microsoft.alpha(opacity="+ value*100 +")"
		}
	}
}
document.write('<div id="testing"></div>');
document.write('<div id="shareButton" onmouseover="shareOver();" onmouseout="shareOut();">');
document.write('<a href="#" onmouseover="shareOver();" onmouseout="shareOut();"><img src="'+bPath+'/share.gif" alt="Share me!" /></a>');
document.write('<div id="shareDrop" >');
document.write('<div class="share" style="width:90px;margin-right:-200px;height:180px;">')
document.write('<a href="'+items[0][1]+'"><img src="'+items[0][3]+'" /> '+items[0][0]+'</a>');
document.write('<a href="'+items[1][1]+'"><img src="'+items[1][3]+'" /> '+items[1][0]+'</a>');
document.write('<a href="'+items[2][1]+'"><img src="'+items[2][3]+'" /> '+items[2][0]+'</a>');
document.write('<a href="'+items[3][1]+'"><img src="'+items[3][3]+'" /> '+items[3][0]+'</a>');
document.write('<a href="'+items[4][1]+'"><img src="'+items[4][3]+'" /> '+items[4][0]+'</a>');
document.write('<a href="'+items[5][1]+'"><img src="'+items[5][3]+'" /> '+items[5][0]+'</a>');
document.write('<a href="'+items[6][1]+'"><img src="'+items[6][3]+'" /> '+items[6][0]+'</a>');
document.write('<a href="'+items[7][1]+'"><img src="'+items[7][3]+'" /> '+items[7][0]+'</a>');
document.write('<a href="'+items[8][1]+'"><img src="'+items[8][3]+'" /> '+items[8][0]+'</a>');
document.write('</div>');

document.write('<div class="share" style="right:0px;top:10px;width:120px;position:absolute;height:180px;">');
document.write('<a href="'+items[9][1]+'"><img src="'+items[9][3]+'" /> '+items[9][0]+'</a>');
document.write('<a href="'+items[10][1]+'"><img src="'+items[10][3]+'" /> '+items[10][0]+'</a>');
document.write('<a href="'+items[11][1]+'"><img src="'+items[11][3]+'" /> '+items[11][0]+'</a>');
document.write('<a href="'+items[12][1]+'"><img src="'+items[12][3]+'" /> '+items[12][0]+'</a>');
document.write('<a href="'+items[13][1]+'"><img src="'+items[13][3]+'" /> '+items[13][0]+'</a>');
document.write('<a href="'+items[14][1]+'"><img src="'+items[14][3]+'" /> '+items[14][0]+'</a>');
document.write('<a href="'+items[15][1]+'"><img src="'+items[15][3]+'" /> '+items[15][0]+'</a>');
document.write('<a href="'+items[16][1]+'"><img src="'+items[16][3]+'" /> '+items[16][0]+'</a>');
document.write('<a href="'+items[17][1]+'"><img src="'+items[17][3]+'" /> '+items[17][0]+'</a>');
document.write('</div>');

document.write('<div class="sharefoot"><a href="http://hardkap.net/pritmarkit" style="color:#fff">Powered by PritMarkit</a></div>');
document.write('</div>');
document.write('<div id="shareshadow"></div>');
document.write('</div>');

