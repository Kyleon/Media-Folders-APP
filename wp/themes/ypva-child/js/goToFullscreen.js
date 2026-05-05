/* Obtiene el elemento de menu por el ID*/

/* Ejecuta la funcion para poner el navegador en pantalla completa */
function lanzaPCompleta(element) {
    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) {
        element.msRequestFullscreen();
    }
}

/* Ejecuta la funcion para restaurar la pantalla del navegador */
function cierraPCompleta() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    }
}


jQuery(document).ready(function() {
    jQuery('#menu-item-9808').hide(); // Oculta el menu restaurar
    //console.log("yeah!");

    jQuery("#menu-item-9798").click(function() {
        lanzaPCompleta(document.documentElement);
        jQuery(this).hide();
        jQuery('#menu-item-9808').show();
    });

});

jQuery(document).ready(function(){
	jQuery('#menu-item-9808').click(
		function(){
			cierraPCompleta(document.documentElement);
			jQuery('#menu-item-9798').show();
			jQuery(this).hide();
		});
});