//Ajax Pagination Script- Author: Dynamic Drive (http://www.dynamicdrive.com)
//** Created Sept 14th, 07'
//** Updated Oct 31st, 07'- Fixed bug when book only contains 1 page
//** Updated Aug 9th 08'- Upgraded to v1.2:
			//1) Adds ability to limit the range of the visible pagination links shown for a book with many pages
			//2) Adds Session only persistence to persist the last page user viewed when navigating away then returning to the webpage containing the script.
			//3) Modified Ajax request function in IE7 to use ActiveXObject object, so script can be tested offline

//** Updated Oct 21st 08'- Upgraded to v1.2.1: Fixed bug with no content showing when there's only one page
//** Updated Dec 1st 08'- v1.2.2: Fixed bug in FF when only one page in book

var ajaxpageclass=new Object()
ajaxpageclass.loadstatustext="Requesting content, please wait..." // HTML to show while requested page is being fetched:
ajaxpageclass.ajaxbustcache=true // Bust cache when fetching pages?
ajaxpageclass.paginatepersist=true //enable persistence of last viewed pagination link (so reloading page doesn't reset page to 1)?
ajaxpageclass.pagerange=4 // Limit page links displayed to a specific number (useful if you have many pages in your book)?
ajaxpageclass.ellipse="..." // Ellipse text (no HTML allowed)

/////////////// No need to edit beyond here /////////////////////////

ajaxpageclass.connect=function(pageurl, divId){
	var page_request = false
	var bustcacheparameter=""
	if (window.XMLHttpRequest && !document.all) // if Mozilla, Safari etc (skip IE7, as object is buggy in that browser)
		page_request = new XMLHttpRequest()
	else if (window.ActiveXObject){ // if IE6 or below
		try {
		page_request = new ActiveXObject("Msxml2.XMLHTTP")
		} 
		catch (e){
			try{
			page_request = new ActiveXObject("Microsoft.XMLHTTP")
			}
			catch (e){}
		}
	}
	else
		return false
	document.getElementById(divId).innerHTML=this.loadstatustext //Display "fetching page message"
	page_request.onreadystatechange=function(){ajaxpageclass.loadpage(page_request, divId)}
	if (this.ajaxbustcache) //if bust caching of external page
		bustcacheparameter=(pageurl.indexOf("?")!=-1)? "&"+new Date().getTime() : "?"+new Date().getTime()
	page_request.open('GET', pageurl+bustcacheparameter, true)
	page_request.send(null)
}

ajaxpageclass.loadpage=function(page_request, divId){
	if (page_request.readyState == 4 && (page_request.status==200 || window.location.href.indexOf("http")==-1)){
		document.getElementById(divId).innerHTML=page_request.responseText
	}
}

ajaxpageclass.getCookie=function(Name){ 
	var re=new RegExp(Name+"=[^;]+", "i"); //construct RE to search for target name/value pair
	if (document.cookie.match(re)) //if cookie found
		return document.cookie.match(re)[0].split("=")[1] //return its value
	return null
}

ajaxpageclass.setCookie=function(name, value){
	document.cookie = name+"="+value
}

ajaxpageclass.getInitialPage=function(divId, pageinfo){
	var persistedpage=this.getCookie(divId)
	var selectedpage=(this.paginatepersist && this.getCookie(divId)!=null)? parseInt(this.getCookie(divId)) : pageinfo.selectedpage
	return (selectedpage>pageinfo.pages.length-1)? 0 : selectedpage //check that selectedpage isn't out of range
}

ajaxpageclass.createBook=function(pageinfo, divId, paginateIds){ //MAIN CONSTRUCTOR FUNCTION
	this.pageinfo=pageinfo //store object containing URLs of pages to fetch, selected page number etc
	this.divId=divId
	this.paginateIds=paginateIds //array of ids corresponding to the pagination DIVs defined for this pageinstance
	//NOTE: this.paginateInfo stores references to various components of each pagination DIV defined for this pageinstance
	//NOTE: Eg: divs[0] = 1st paginate div, pagelinks[0][0] = 1st page link within 1st paginate DIV, prevlink[0] = previous link within paginate DIV etc
	this.paginateInfo={divs:[], pagelinks:[[]], prevlink:[], nextlink:[], previouspage:null, previousrange:[null,null], leftellipse:[], rightellipse:[]}
	this.dopagerange=false
	this.pagerangestyle=''
	this.ellipse='<span style="display:none">'+ajaxpageclass.ellipse+'</span>' //construct HTML for ellipse
	var initialpage=ajaxpageclass.getInitialPage(divId, pageinfo)
	this.buildpagination(initialpage)
	this.selectpage(initialpage)
}

ajaxpageclass.createBook.prototype={

	buildpagination:function(selectedpage){ //build pagination links based on length of this.pageinfo.pages[]
		this.dopagerange=(this.pageinfo.pages.length>ajaxpageclass.pagerange) //Bool: enable limitpagerange if pagerange value is less than total pages available
		this.pagerangestyle=this.dopagerange? 'style="display:none"' : '' //if limitpagerange enabled, hide pagination links when building them
		this.paginateInfo.previousrange=null //Set previousrange[start, finish] to null to start
		if (this.pageinfo.pages.length<=1){ //no 0 or just 1 page
			document.getElementById(this.paginateIds[0]).innerHTML=(this.pageinfo.pages.length==1)? "Page 1 of 1" : ""
			return
		}
		else{ //construct paginate interface
			var paginateHTML='<div class="pagination"><ul>\n'
			paginateHTML+='<li><a href="#previous" onclick="return false" rel="'+(selectedpage-1)+'">«</a></li>\n' //previous link HTML
			for (var i=0; i<this.pageinfo.pages.length; i++){
				var ellipses={left: (i==0? this.ellipse : ''), right: (i==this.pageinfo.pages.length-1? this.ellipse : '')} //if this is 1st or last page link, add ellipse next to them, hidden by default
				paginateHTML+='<li>'+ellipses.right+'<a href="#page'+(i+1)+'" onclick="return false" rel="'+i+'" '+this.pagerangestyle+'>'+(i+1)+'</a>'+ellipses.left+'</li>\n'
			}
			paginateHTML+='<li><a href="#next" onclick="return false" rel="'+(selectedpage+1)+'">next »</a></li>\n' //next link HTML
			paginateHTML+='</ul></div>'
		}// end construction
		this.paginateInfo.previouspage=selectedpage //remember last viewed page
		for (var i=0; i<this.paginateIds.length; i++){ //loop through # of pagination DIVs specified
			var paginatediv=document.getElementById(this.paginateIds[i]) //reference pagination DIV
			this.paginateInfo.divs[i]=paginatediv //store ref to this paginate DIV
			paginatediv.innerHTML=paginateHTML
			var paginatelinks=paginatediv.getElementsByTagName("a")
			var ellipsespans=paginatediv.getElementsByTagName("span")
			this.paginateInfo.prevlink[i]=paginatelinks[0]
			if (paginatelinks.length>0)
				this.paginateInfo.nextlink[i]=paginatelinks[paginatelinks.length-1]
			this.paginateInfo.leftellipse[i]=ellipsespans[0]
			this.paginateInfo.rightellipse[i]=ellipsespans[1]
			this.paginateInfo.pagelinks[i]=[] //array to store the page links of pagination DIV
			for (var p=1; p<paginatelinks.length-1; p++){
				this.paginateInfo.pagelinks[i][p-1]=paginatelinks[p]
			}
			var pageinstance=this
			paginatediv.onclick=function(e){
				var targetobj=window.event? window.event.srcElement : e.target
				if (targetobj.tagName=="A" && targetobj.getAttribute("rel")!=""){
					if (!/disabled/i.test(targetobj.className)){ //if this pagination link isn't disabled (CSS classname "disabled")
						pageinstance.selectpage(parseInt(targetobj.getAttribute("rel")))
					}
				}
				return false
			}
		}
	},

	selectpage:function(selectedpage){
		//replace URL's root domain with dynamic root domain (with or without "www"), for ajax security sake:
		if (this.pageinfo.pages.length>0){
			var ajaxfriendlyurl=this.pageinfo.pages[selectedpage].replace(/^http:\/\/[^\/]+\//i, "http://"+window.location.hostname+"/")
			ajaxpageclass.connect(ajaxfriendlyurl, this.divId) //fetch requested page and display it inside DIV
		}
		if (this.pageinfo.pages.length<=1) //if this book only contains only 1 page (or 0)
			return //stop here
		var paginateInfo=this.paginateInfo
		for (var i=0; i<paginateInfo.divs.length; i++){ //loop through # of pagination DIVs specified
			//var paginatediv=document.getElementById(this.paginateIds[i])
			paginateInfo.prevlink[i].className=(selectedpage==0)? "prevnext disabled" : "prevnext" //if current page is 1st page, disable "prev" button
			paginateInfo.prevlink[i].setAttribute("rel", selectedpage-1) //update rel attr of "prev" button with page # to go to when clicked on
			paginateInfo.nextlink[i].className=(selectedpage==this.pageinfo.pages.length-1)? "prevnext disabled" : "prevnext"
			paginateInfo.nextlink[i].setAttribute("rel", selectedpage+1)
			paginateInfo.pagelinks[i][paginateInfo.previouspage].className="" //deselect last clicked on pagination link (previous)
			paginateInfo.pagelinks[i][selectedpage].className="currentpage" //select current pagination link
		}
		paginateInfo.previouspage=selectedpage //Update last viewed page info
		ajaxpageclass.setCookie(this.divId, selectedpage)
		this.limitpagerange(selectedpage) //limit range of page links displayed (if applicable)
	},

	limitpagerange:function(selectedpage){
		//reminder: selectedpage count starts at 0 (0=1st page)
		var paginateInfo=this.paginateInfo
		if (this.dopagerange){
			var visiblelinks=ajaxpageclass.pagerange-1 //# of visible page links other than currently selected link
			var visibleleftlinks=Math.floor(visiblelinks/2) //calculate # of visible links to the left of the selected page
			var visiblerightlinks=visibleleftlinks+(visiblelinks%2==1? 1 : 0) //calculate # of visible links to the right of the selected page
			if (selectedpage<visibleleftlinks){ //if not enough room to the left to accomodate all visible left links
				var overage=visibleleftlinks-selectedpage
				visibleleftlinks-=overage //remove overage links from visible left links
				visiblerightlinks+=overage //add overage links to the visible right links
			}
			else if ((this.pageinfo.pages.length-selectedpage-1)<visiblerightlinks){ //else if not enough room to the left to accomodate all visible right links
				var overage=visiblerightlinks-(this.pageinfo.pages.length-selectedpage-1)
				visiblerightlinks-=overage //remove overage links from visible right links
				visibleleftlinks+=overage //add overage links to the visible left links
			}
			var currentrange=[selectedpage-visibleleftlinks, selectedpage+visiblerightlinks] //calculate indices of visible pages to show: [startindex, endindex]
			var previousrange=paginateInfo.previousrange //retrieve previous page range
			for (var i=0; i<paginateInfo.divs.length; i++){ //loop through paginate divs
				if (previousrange){ //if previous range is available (not null)
					for (var p=previousrange[0]; p<=previousrange[1]; p++){ //hide all page links
						paginateInfo.pagelinks[i][p].style.display="none"
					}
				}
				for (var p=currentrange[0]; p<=currentrange[1]; p++){ //reveal all active page links
					paginateInfo.pagelinks[i][p].style.display="inline"
				}
				paginateInfo.pagelinks[i][0].style.display="inline" //always show 1st page link
				paginateInfo.pagelinks[i][this.pageinfo.pages.length-1].style.display="inline" //always show last page link
				paginateInfo.leftellipse[i].style.display=(currentrange[0]>1)? "inline" : "none" //if starting page is page3 or higher, show ellipse to page1
				paginateInfo.rightellipse[i].style.display=(currentrange[1]<this.pageinfo.pages.length-2)? "inline" : "none" //if end page is 2 pages before last page or less, show ellipse to last page
			}
		}
			paginateInfo.previousrange=currentrange
	},

	refresh:function(pageinfo){
	this.pageinfo=pageinfo
	var initialpage=ajaxpageclass.getInitialPage(this.divId, pageinfo)
	this.buildpagination(initialpage)
	this.selectpage(initialpage)
	}
}