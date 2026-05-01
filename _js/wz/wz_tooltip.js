// Copyright (c) 2002-2007 Walter Zorn. All rights reserved.
// Created 1. 12. 2002 by Walter Zorn (Web: http://www.walterzorn.com )
// Last modified: 15. 7. 2007
//
// CrossBrowser Tooltip Library
// file: wz_tooltip.js
//
// TERMS OF USE
// This library is free for all kinds of use, commercial or not,
// if the copyright notice above and this terms-of-use notice
// is left intact in the source code.


//==================  GLOBAL TOOPTIP CONFIGURATION  =========================
var config = new Object();

// Tooltip appearance
config.BgColor     = '#E2E7FF';
config.BgImg       = '';    // path to background image;
config.BorderColor = '#003399';
config.BorderStyle = 'solid';
config.BorderWidth = 1;
config.FontColor   = '#000066';
config.FontFace    = 'arial,helvetica,sans-serif';
config.FontSize    = '11px';
config.FontWeight  = 'normal';
config.TextAlign   = 'left';
config.Width       = 300;
config.PadTextTop    = 3;
config.PadTextLeft   = 3;
config.PadTextBottom = 3;
config.PadTextRight  = 3;

// Tooltip positioning
config.MouseOffsetX = 8;
config.MouseOffsetY = 8;
config.Direction = 'southeast';

// Tooltip timing
config.Duration = -1;  // time span until tooltip disappears; in milliseconds
config.ResetDuration = false;  // true->resets duration on every mouseover
config.FadeIn  = 0;
config.FadeOut = 0;

// Tooltip borders and shadow
config.ShadowType = 'drop-shadow'; // 'simple', 'drop-shadow', or ''
config.ShadowColor = '#C0C0C0';

// Tooltip behaviour
config.Sticky     = false;
config.FollowMouse = true;
config.CheckRightBounds = true;
config.CheckBottomBounds = true;

//=============  END OF TOOLTIP CONFIG, DO NOT CHANGE ANYTHING BELOW  ============


//============  TOOLTIP CORE FUNCTIONALITY  ======================

var tt, tt_w, tt_h, tt_x, tt_y, tt_int = null, tt_text = '', tt_db = document.body && document.body.style, tt_musover = false, tt_obj = null;

var tipIsOn = 0, scrollX = 0, scrollY = 0, width = 0, height = 0;
var CSS = (document.body && document.body.style && typeof(document.body.style.maxWidth) != "undefined"), n4 = (document.layers && typeof document.classes != "undefined"), n6 = (document.getElementById && !document.all && !n4);

function tt_Int()
{
    var x = 0, y = 0, v_w = 0, v_h = 0;
    if(typeof(window.pageYOffset) == 'number') //NetScape
    {
        x = window.pageXOffset;
        y = window.pageYOffset;
        v_w = window.innerWidth;
        v_h = window.innerHeight;
    }
    else if(document.body && (document.body.scrollLeft || document.body.scrollTop)) // IE
    {
        x = document.body.scrollLeft;
        y = document.body.scrollTop;
        v_w = document.body.clientWidth;
        v_h = document.body.clientHeight;
    }
    else if(document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) // IE6 StrictMode
    {
        x = document.documentElement.scrollLeft;
        y = document.documentElement.scrollTop;
        v_w = document.documentElement.clientWidth;
        v_h = document.documentElement.clientHeight;
    }
    scrollX = x; scrollY = y; width = v_w; height = v_h;
}

function tt_releasecapture()
{
    if(n4) document.releaseEvents(Event.MOUSEMOVE);
    else if(document.onmousemove == tt_moveHandler) document.onmousemove = null;
}

function tt_capturemouse()
{
    tt_int = setInterval('tt_moveHandler(tt_e)', 5);
}

var tt_e;
function tt_moveHandler(e)
{
    if(e) tt_e = e;
    else if(window.event) tt_e = window.event;
    else return;
    if(tt_obj) tt_setPos();
}

function tt_setPos()
{
    if(!tt_obj) return;
    if(!tt_e) return;

    tt_Int();

    var mouse_x, mouse_y;
    if(n4 || n6)
    {
        mouse_x = (tt_e && tt_e.pageX) ? tt_e.pageX : ((tt_e && tt_e.clientX) ? tt_e.clientX : 0);
        mouse_y = (tt_e && tt_e.pageY) ? tt_e.pageY : ((tt_e && tt_e.clientY) ? tt_e.clientY : 0);
        if(n6 && scrollX) mouse_x += scrollX;
        if(n6 && scrollY) mouse_y += scrollY;
    }
    else
    {
        mouse_x = ((tt_e && tt_e.clientX) ? tt_e.clientX : 0) + scrollX;
        mouse_y = ((tt_e && tt_e.clientY) ? tt_e.clientY : 0) + scrollY;
    }

    tt_x = mouse_x + config.MouseOffsetX;
    tt_y = mouse_y + config.MouseOffsetY;

    var v_rt = scrollX + width, v_bt = scrollY + height;

    if(config.CheckRightBounds && (tt_x + tt_w > v_rt))
        tt_x = v_rt - tt_w;
    if(config.CheckBottomBounds && (tt_y + tt_h > v_bt))
        tt_y = v_bt - tt_h;

    if(tt_x < scrollX) tt_x = scrollX;
    if(tt_y < scrollY) tt_y = scrollY;

    tt_obj.style.left = tt_x + 'px';
    tt_obj.style.top = tt_y + 'px';
}

function tt_showTooltip(text)
{
    if(tt_obj) tt_hideTooltip();

    if(!text || text == '') return;

    if(typeof(text) != 'string') text = '' + text;
    tt_text = text.replace(/&lt;.*?&gt;/g, '');

    // Create tooltip div
    tt_obj = document.createElement('div');
    tt_obj.style.position = 'absolute';
    tt_obj.style.left = '0px';
    tt_obj.style.top = '0px';
    tt_obj.style.visibility = 'hidden';
    tt_obj.style.zIndex = 1000;

    // Style the tooltip
    tt_obj.style.backgroundColor = config.BgColor;
    tt_obj.style.border = config.BorderWidth + 'px ' + config.BorderStyle + ' ' + config.BorderColor;
    tt_obj.style.color = config.FontColor;
    tt_obj.style.fontFamily = config.FontFace;
    tt_obj.style.fontSize = config.FontSize;
    tt_obj.style.fontWeight = config.FontWeight;
    tt_obj.style.textAlign = config.TextAlign;
    tt_obj.style.width = config.Width + 'px';
    tt_obj.style.padding = config.PadTextTop + 'px ' + config.PadTextRight + 'px ' + config.PadTextBottom + 'px ' + config.PadTextLeft + 'px';

    tt_obj.innerHTML = text;
    document.body.appendChild(tt_obj);

    // Get tooltip dimensions
    tt_w = tt_obj.offsetWidth;
    tt_h = tt_obj.offsetHeight;

    tt_obj.style.visibility = 'visible';
    tt_setPos();

    if(config.FollowMouse) tt_capturemouse();

    tipIsOn = 1;
}

function tt_hideTooltip()
{
    if(tt_obj)
    {
        document.body.removeChild(tt_obj);
        tt_obj = null;
        tt_releasecapture();
        tipIsOn = 0;
    }
}

// Main tooltip functions
function Tip(text, width, bgcolor, bordercolor, fontsize, fontcolor, fontweight, fontstyle, textcolor, caption, above, textalign, left, delay, duration, parent, offsetx, offsety, fade, t_background, shadow, opacity, w_filter)
{
    if(text) {
        // Capture current mouse event if available
        if(window.event) {
            tt_e = window.event;
        } else if(arguments.callee.caller && arguments.callee.caller.arguments && arguments.callee.caller.arguments[0]) {
            tt_e = arguments.callee.caller.arguments[0];
        }
        tt_showTooltip(text);
    }
}

function UnTip()
{
    tt_hideTooltip();
}

// Make functions globally available
window.Tip = Tip;
window.UnTip = UnTip;