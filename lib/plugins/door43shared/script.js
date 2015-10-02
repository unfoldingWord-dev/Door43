
function disable_page() {
    var cover = document.getElementById('gray-cover');

    // create the div if it doesn't already exist
    if (!cover) {
        cover = document.createElement('div');
        cover.id = 'gray-cover';
        cover.setAttribute('style', 'position: fixed; top: 0; left: 0; overflow: hidden; display: none; width: 100%; height: 100%; background-color: #000; z-index: 150; opacity: 0.5; cursor: wait;');
        document.body.appendChild(cover);
    }
    cover.style.display = 'inline-block';
}

function enable_page() {
    var cover = document.getElementById('gray-cover');
    cover.style.display = 'none';
}

// not all browsers support string.startsWith
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
        position = position || 0;
        return this.indexOf(searchString, position) === position;
    };
}
