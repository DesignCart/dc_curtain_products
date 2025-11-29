(function() {
    function init() {
        var speed = 330,
            easing = mina.backout;

        [].slice.call ( document.querySelectorAll( '.dc-curtain-products-row .dc-curtain-products-item' ) ).forEach( function( el ) {
            var s = Snap( el.querySelector( 'svg' ) ),
                path = s.select( 'path' ),
                originalPathD = path.attr( 'd' ), // Zachowaj oryginalny kształt SVG z definicji
                hoverPathD = el.getAttribute( 'data-path-hover' ); // Zachowaj kształt "falujący" z atrybutu

            // NOWE: Ustaw domyślny kształt SVG na ten, który był "po hoverze"
            path.attr( { 'd' : hoverPathD } );

            el.addEventListener( 'mouseenter', function() {
                // Po najechaniu animujemy DO oryginalnego kształtu
                path.animate( { 'path' : originalPathD }, speed, easing );
            } );

            el.addEventListener( 'mouseleave', function() {
                // Po opuszczeniu animujemy Z POWROTEM do kształtu "falującego" (czyli domyślnego teraz)
                path.animate( { 'path' : hoverPathD }, speed, easing );
            } );
        } );
    }
    init();
})();