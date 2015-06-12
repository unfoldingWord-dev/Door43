/* Add Fields button to the toolbar */
/* from http://wiki.splitbrain.org/wiki:tips:toolbarbutton */

if(toolbar){ 
    toolbar[toolbar.length] = 
        {"type":"insert", "title":"Field", "key":"", 
         "icon":"../../plugins/fields/field.png", 
         'insert': '{{fields>}}'
        }; 
}
