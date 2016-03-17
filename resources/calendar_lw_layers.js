//	written	by Tan Ling Wee
//	last updated 20 June 2003
//	email :	fuushikaden@yahoo.com

//////////////////////////////////////////////////////////////////////////////////
// Layers
//////////////////////////////////////////////////////////////////////////////////

var layerQueue=new Array()
var layerIndex=-1

/* hides <select> and <applet> objects (for IE only) */
function hideElement( elmID, overDiv )
{
  if( ie )
  {
	for( i = 0; i < document.getElementsByTagName( elmID ).length; i++ )
	{
	  obj = document.getElementsByTagName( elmID )[i];
	  if( !obj || !obj.offsetParent )
	  {
		continue;
	  }

	  // Find the element's offsetTop and offsetLeft relative to the BODY tag.
	  objLeft   = obj.offsetLeft;
	  objTop    = obj.offsetTop;
	  objParent = obj.offsetParent;

	  while( objParent.tagName.toUpperCase() != "BODY" )
	  {
		objLeft  += objParent.offsetLeft;
		objTop   += objParent.offsetTop;
		objParent = objParent.offsetParent;
	  }

	  objHeight = obj.offsetHeight;
	  objWidth = obj.offsetWidth;

	  if(( overDiv.offsetLeft + overDiv.offsetWidth ) <= objLeft );
	  else if(( overDiv.offsetTop + overDiv.offsetHeight ) <= objTop );
	  else if( overDiv.offsetTop >= ( objTop + objHeight ));
	  else if( overDiv.offsetLeft >= ( objLeft + objWidth ));
	  else
	  {
		obj.style.visibility = "hidden";
	  }
	}
  }
}

/*
* unhides <select> and <applet> objects (for IE only)
*/
function showElement( elmID )
{
  if( ie )
  {
	for( i = 0; i < document.getElementsByTagName( elmID ).length; i++ )
	{
	  obj = document.getElementsByTagName( elmID )[i];

	  if( !obj || !obj.offsetParent )
	  {
		continue;
	  }

	  obj.style.visibility = "";
	}
  }
}

function lw_createLayer (layerName, top_pos, left_pos, width, height, bgcolor, bordercolor, z_index) {
	document.write("<div ONCLICK='event.cancelBubble=true' id='"+layerName+"' style='z-index:" + z_index + ";position:absolute;top:"+top_pos+";left:"+left_pos+";visibility:hidden;'><table bgcolor='"+bgcolor+"' style='border-width:1px;border-style:solid;border-color:" + bordercolor + "' cellpadding=2 cellspacing=0 width=0><tr><td valign=top width='"+width+"' height='"+height+"'><span id='"+layerName+"_content'></span></td></tr></table></div>")
}

function lw_getObj (objName) {
	return (dom)?document.getElementById(objName).style:ie?eval("document.all."+objName) :eval("document."+objName)
}

function lw_showLayer (layerName) {

	found=false
	for (i=0;i<=layerIndex;i++)
	{
		if (layerQueue[i]==layerName)
		{
			found=true
		}
	}

	if ((lw_getObj(layerName).visibility!="visible")&&(lw_getObj(layerName).visibility!="show"))
	{
		lw_getObj(layerName).visibility = (dom||ie)?"visible":"show"
		layerQueue[++layerIndex] = layerName

		hideElement( 'SELECT', document.getElementById(layerName) );
		hideElement( 'APPLET', document.getElementById(layerName) );
	}
}

function lw_hideLayer () {
	showElement( 'SELECT', document.getElementById(layerQueue[layerIndex]) );
	showElement( 'APPLET', document.getElementById(layerQueue[layerIndex]) );

	lw_getObj(layerQueue[layerIndex--]).visibility = "hidden"
}

function lw_hideLayerName (layerName) {
	var i
	var tmpQueue=new Array()
	var newIndex=-1

	showElement( 'SELECT', document.getElementById(layerName) );
	showElement( 'APPLET', document.getElementById(layerName) );

	lw_getObj(layerName).visibility = "hidden"

	for (i=0;i<=layerIndex;i++)
	{
		if ((layerQueue[i]!="")&&(layerQueue[i]!=layerName))
		{
			tmpQueue [++newIndex] = layerQueue[i]
			hideElement( 'SELECT', document.getElementById(layerQueue[i]) );
			hideElement( 'APPLET', document.getElementById(layerQueue[i]) );
		}

	}

	layerQueue = tmpQueue
	layerIndex = newIndex
}

function lw_closeAllLayers() {
	while (layerIndex >= 0)
	{
		lw_hideLayer()
	}
}

function lw_closeLastLayer() {
	if (layerIndex >= 0)
	{
		while ((lw_getObj(layerQueue[layerIndex]).visibility!="visible") && (layerIndex>0))
		{
			layerIndex--;
		}
		lw_hideLayer()
	}
}

function lw_escLayer (e) {
	if (navigator.appName=="Netscape")
	{
		var keyCode = e.keyCode?e.keyCode:e.which?e.which:e.charCode;
		if ((keyCode==27)||(keyCode==1))
		{
			lw_closeLastLayer()
		}
	}
	else
	if ((event.keyCode==0)||(event.keyCode==27))
	{
		lw_closeLastLayer()
	}
}


var lw_leftpos = 0
var lw_toppos = 0
var lw_width = 0
var lw_height = 0

function lw_calcpos(obj) {
	lw_leftpos=0
	lw_toppos=0
	lw_width = obj.offsetWidth
	lw_height = obj.offsetHeight

	var aTag = obj
	do {
		lw_leftpos += aTag.offsetLeft;
		lw_toppos += aTag.offsetTop;
		aTag = aTag.offsetParent;
	} while(aTag.tagName!="BODY");
}

document.onkeypress = lw_escLayer;
document.onclick = lw_closeAllLayers;
