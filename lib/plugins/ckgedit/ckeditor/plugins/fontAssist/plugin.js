CKEDITOR.plugins.add("fontAssist",{init:function(b){b.addCommand("fontAssistDialog",new CKEDITOR.dialogCommand("fontAssistDialog"));var d=b.lang.fontassist;if(!d){d=CKEDITOR.lang["default"]["fontassist"]}else{var c=CKEDITOR.lang["default"]["fontassist"];for(var a in c){if(!d[a]){d[a]=c[a]}}}b.ui.addButton("FontAssist",{label:d.ToolTip,command:"fontAssistDialog",icon:this.path+"images/fonts.png"});CKEDITOR.dialog.add("fontAssistDialog",function(g){var x;var t;var l=false;var r={fg:"",bg:"",font_style:"",font:"",font_size:"",start:"&lt;",close:"&gt;",save_color:function(A,z){this[A]=z},get:function(z){return this[z]},save_font:function(z){this["font"]=z},save_size:function(z){this["font_size"]=z},get_font_style:function(A){var z=A?"/":" ";return this["font_size"]+z+this["font"]},open_tag:function(){return this.start+"font "+this.get_font_style(true)+";;"+this.get("fg")+";;"+this.get("bg")+this.close},close_tag:function(){return this.start+"/font"+this.close},};var e={fg:"",bg:"",font:"",font_size:"",};var h=function(A,D,C){var z=A.getContentElement(D,C).getInputElement().$.id;return document.getElementById(z)};var k=function(A){var z=h(A,"general","alert");z.value=r.open_tag();z.value=z.value.replace(/&lt;/,"<");z.value=z.value.replace(/&gt;/,">")};var f=function(A,z){var E=(z=="colors")?"fg":"bg";var C=h(A,z,E);r.save_color(E,C.value);var C=h(A,"general","contents");if(E=="fg"){C.style.color=r.get(E);var D=h(A,"general","contents");D.innerHTML=v(t)}else{C.style.backgroundColor=r.get(E)}A.enableButton("ok");A.selectPage("general");k(A)};var n=function(z){r.save_color("fg",e.fg);r.save_color("bg",e.bg);var A=h(z,"general","contents");A.style.color=r.get("fg");A.style.backgroundColor=r.get("bg");A.innerHTML=v(t);k(z)};var u=function(z){var A=r.get_font_style(false);var C=h(z,"general","contents");C.style.font=A;C.innerHTML=v(t);k(z)};var p=function(z){r.save_font(e.font);r.save_size(e.font_size);u(z);var A=h(z,"general","fontopts");A.options[0].selected=true;A=h(z,"general","sizeopts");A.options[0].selected=true};var y=function(z,D){var A=this.getDialog();A.disableButton("ok");var E=h(A,"colors","fg");var C=o(z,E);var E=h(A,"colors","fgsample");E.style.backgroundColor=C;w(A,true)};var q=function(z,D){var A=this.getDialog();A.disableButton("ok");var E=h(A,"backgroundcolors","bg");var C=o(z,E);var E=h(A,"backgroundcolors","bgsample");E.style.backgroundColor=C;w(A,true)};var o=function(z,F){var E=z.data.getTarget(),C=E.getName();if(C!="td"){return}var A=E.getAttribute("style").split(/#/);var D=E.getAttribute("style").match(/(#[A-Z0-9]+);/);if(!D){D=E.getAttribute("style").match(/(rgb\([,\s\d]+\))/)}else{D[1]=s(D[1])}F.value=D[1];return D[1]};var w=function(A,C){var z=h(A,"general","oktoggle");z.checked=C};var s=function(E){var A=function(F){return parseInt((C(F)).substring(0,2),16)};var z=function(F){return parseInt((C(F)).substring(2,4),16)};var D=function(F){return parseInt((C(F)).substring(4,6),16)};var C=function(F){return(F.charAt(0)=="#")?F.substring(1,7):F};R=A(E);G=z(E);B=D(E);return"rgb("+R+", "+G+", "+B+")"};var j=function(D,C){for(var A=0;A<C.length;A++){var z=C[A].split("/");D.options[D.options.length]=new Option(z[0],z[1])}};var i=function(D,C){for(var z=0;z<D.options.length;z++){var A=new RegExp(D.options[z].label,"i");if(C.match(A)){D.options[z].selected=true;break}}};function m(){if(x){return x.join("")}x=['<div style="width:90%;margin:auto; height: 400px; overflow:auto;">','<style type="text/css">#c_chart td { height: 16px; width: 16px; border: 2px solid white; }</style>','<table  id = "c_chart"  style="border:2px solid white;" cellspacing="2" cellpadding="2"><tbody><tr>'];var C=g.config.colors;for(var z=0;z<C.length;z++){if(z%26==0){x.push("<tr>")}x.push('<td style="background-color: #'+C[z]+';" title= #"'+C[z]+'"></td>')}x.push("</tbody></table></div>");var A=x.join("");return A}function v(z){z=z.replace(/<(b|i|em|u|strong|sup|sub|code)>/g,function(A,K){var C=r.get_font_style(false),I=r.get("fg"),D=r.get("font"),J=r.get("font_size");var F="";var H="";switch(K){case"b":case"strong":F="; font-weight: bold; ";break;case"i":case"em":F="; font-style: italic; ";break;case"u":F="; text-decoration:underline; ";break;case"sup":var E=parseInt(J);E=parseInt(E*0.75);F="; vertical-align:super; ";H=E+"pt "+D;break;case"sub":var E=parseInt(J);E=parseInt(E*0.75);F="; vertical-align: sub; ";H=E+"pt "+D;break;case"code":I="black";F="; background-color: white; ";break}return"<"+K+" style='color:"+I+"; font: "+(H?H:C)+F+"'>"});return z}return{title:d.Title,minWidth:440,minHeight:480,contents:[{id:"general",label:d.Main,elements:[{type:"html",html:d.MainHeader,},{type:"html",html:'<div style="max-width:400px;  white-space: pre-wrap;border:1px solid black; margin:auto; height: 300px; overflow:auto;"></div>',id:"contents",label:d.Text,},{type:"text",id:"alert",onClick:function(){var z=this.getDialog();var A=h(z,"general","alert");A.focus();A.select()},},{id:"oktoggle",label:d.IfChecked,type:"checkbox","default":false,onChange:function(){var z=this.getDialog();z.enableButton("ok");w(z,false)},},{type:"hbox",children:[{type:"select",id:"fontopts",label:d.Fonts,items:[["< "+d.none+" >",""]],onChange:function(){r.save_font(this.getValue());u(this.getDialog())},commit:function(z){z.style=this.getValue()}},{type:"select",id:"sizeopts",label:d.FontSizes,items:[["< "+d.none+" >",""]],onChange:function(){r.save_size(this.getValue());u(this.getDialog())},commit:function(z){z.style=this.getValue()}},]},{type:"hbox",children:[{type:"button",label:d.ResetFont,onClick:function(){p(this.getDialog())},},{type:"button",label:d.ResetAll,onClick:function(){var z=this.getDialog();p(z);n(z)},},]},]},{id:"colors",label:d.TextColors,elements:[{type:"html",html:d.SelectColor,},{id:"textcolors",type:"html",html:m(),onClick:y},{type:"hbox",widths:["40%","10%","25%","25%"],children:[{type:"text",id:"fg",width:"12em",label:d.SelectedTextColor,commit:function(z){z.fg=this.getValue()}},{type:"html",id:"fgsample",html:'<table style="border: 1px solid silver;"><tr><td style="width:24px;">&nbsp;</td><tr></table>',},{type:"button",id:"fgb_confirm",label:d.Accept,onClick:function(){var z=this.getDialog();f(z,"colors");z.enableButton("ok");w(z,false)},},{type:"button",id:"fgb_reset",label:d.Reset,onClick:function(){var z=this.getDialog();var A=h(z,"colors","fg");A.value="";z.enableButton("ok");w(z,false)},},],}],},{id:"backgroundcolors",label:d.BGColors,elements:[{type:"html",html:d.SelectColor,},{id:"bgcolors",type:"html",html:m(),onClick:q},{type:"hbox",widths:["40%","10%","25%","25%"],children:[{type:"text",id:"bg",width:"12em",label:d.SelectedBGColor,commit:function(z){z.fg=this.getValue()}},{type:"html",id:"bgsample",html:'<table style="border: 1px solid silver;"><tr><td style="width:24px;">&nbsp;</td><tr></table>',},{type:"button",id:"bgb_confirm",label:d.Accept,onClick:function(){var z=this.getDialog();f(z,"backgroundcolors");z.enableButton("ok");w(z,false)},},{type:"button",id:"bgb_reset",label:d.Reset,onClick:function(){var z=this.getDialog();var A=h(z,"backgroundcolors","bg");A.value="";z.enableButton("ok");w(z,false)},},],},],},{id:"info",label:"Info",elements:[{type:"html",html:d.InfoText,}],}],onShow:function(){var J=this.getParentEditor(),N=J.getSelection();l=false;var M=h(this,"general","alert");M.innerHTML="";this.enableButton("ok");var D=N.getSelectedText();var I=N.getRanges(true)[0];I.shrink(CKEDITOR.SHRINK_TEXT);var L=I.getCommonAncestor();var A=L.getAscendant("p",true);if(!A){return}if(!D.match(/&lt;font(.*)\/font&gt;/)&&!D.match(/<font(.*)\/font>/)){l=true;var M=h(this,"general","alert");M.value="Changes will not be inserted into editor.  See Info for details"}text=A.getHtml();if(!text&&l){if(D){text=D}}text=text.replace(/&lt;/g,"<");text=text.replace(/&gt;/g,">");text=text.replace(/<span.*?scayt.*?>(.*?)<\/span>/g,"$1");var K=h(this,"general","contents");var H=text.match(/<font(.*?)>(.*)/m);if(H&&H[2]){var z=H[1].split(/;;/);K.style.color=z[1];K.style.backgroundColor=z[2];r.save_color("fg",z[1]);r.save_color("bg",z[2]);e.fg=z[1];e.bg=z[2];var E=z[0].split("/");var F=E[0]+" "+E[1];K.style.font=F;r.save_size(E[0]);r.save_font(E[1]);e.font=E[1];e.font_size=E[0];H[2]=H[2].replace(/<\/font>/,"");H[2]=H[2].replace(/<br\/?>/," ");t=H[2];K.innerHTML=v(H[2]);var C=h(this,"general","fontopts");i(C,e.font);C=h(this,"general","sizeopts");i(C,e.font_size)}},onLoad:function(){g=this.getParentEditor();var E=h(this,"general","fontopts");var D=CKEDITOR.config.font_names.split(/;/);j(E,D);var E=h(this,"general","sizeopts");var z=CKEDITOR.config.fontSize_sizes.split(/;/);j(E,z);var C=this._.tabs.general&&this._.tabs.general[0];var A=this;C.on("focus",function(F){A.enableButton("ok");var H=h(A,"general","oktoggle");H.checked=false})},onOk:function(){if(l){return}var A=g.document.createElement("p");var z=r.open_tag()+t+r.close_tag();A.setHtml(z);g.insertElement(A)}}})}});