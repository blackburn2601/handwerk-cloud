/*
 * Sketch pad.
 *
 * Uses Pointer Events so mouse, touch and stylus run through one code path —
 * the previous implementation kept separate mouse and touch handlers that
 * could drift apart.
 *
 * On submit the canvas is serialised into the hidden TaskDraw::base64Data
 * field, which the server decodes and stores as a PNG.
 */
(function () {
    'use strict';

    var canvas = document.getElementById('canvas');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var widthSelect = document.getElementById('selWidth');
    var swatches = document.getElementById('swatches');
    var target = document.getElementById('task_draw_base64Data');
    var form = document.getElementById('sketch-form');

    var colour = '#0f172a';
    var drawing = false;
    var undoStack = [];

    /* A transparent PNG would print badly, so start from white. */
    function fill() {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
    fill();

    function snapshot() {
        undoStack.push(canvas.toDataURL());
        if (undoStack.length > 25) undoStack.shift();
    }

    /* The canvas is scaled by CSS, so map client coords onto its real size. */
    function pos(event) {
        var rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    function start(event) {
        if (event.button !== undefined && event.button !== 0) return;
        event.preventDefault();
        snapshot();
        drawing = true;
        try { canvas.setPointerCapture(event.pointerId); } catch (e) { /* not capturable */ }

        var p = pos(event);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = colour;
        ctx.lineWidth = parseFloat(widthSelect ? widthSelect.value : 2);

        /* A tap without movement should still leave a dot. */
        ctx.lineTo(p.x + 0.01, p.y);
        ctx.stroke();
    }

    function move(event) {
        if (!drawing) return;
        event.preventDefault();
        var p = pos(event);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }

    function stop(event) {
        if (!drawing) return;
        drawing = false;
        try { canvas.releasePointerCapture(event.pointerId); } catch (e) { /* already released */ }
        ctx.closePath();
    }

    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', stop);
    canvas.addEventListener('pointercancel', stop);
    canvas.addEventListener('pointerleave', stop);

    /* Colour picker ------------------------------------------------------ */
    if (swatches) {
        swatches.addEventListener('click', function (event) {
            var button = event.target.closest('.swatch');
            if (!button) return;
            colour = button.dataset.color;
            swatches.querySelectorAll('.swatch').forEach(function (s) {
                s.classList.toggle('is-active', s === button);
            });
        });
    }

    /* Undo / clear ------------------------------------------------------- */
    var undoButton = document.getElementById('btn-undo');
    if (undoButton) {
        undoButton.addEventListener('click', function () {
            var previous = undoStack.pop();
            if (!previous) return;
            var image = new Image();
            image.onload = function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(image, 0, 0);
            };
            image.src = previous;
        });
    }

    var clearButton = document.getElementById('btn-clear');
    if (clearButton) {
        clearButton.addEventListener('click', function () {
            if (!window.confirm('Möchten Sie die Zeichnung wirklich löschen?')) return;
            snapshot();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            fill();
        });
    }

    /* Fullscreen --------------------------------------------------------- */
    var fullscreenButton = document.getElementById('btn-fullscreen');
    if (fullscreenButton) {
        fullscreenButton.addEventListener('click', function () {
            var wrap = document.getElementById('canvas-wrap');
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else if (wrap && wrap.requestFullscreen) {
                wrap.requestFullscreen();
            }
        });
    }

    /* Serialise on submit ------------------------------------------------ */
    if (form && target) {
        form.addEventListener('submit', function () {
            target.value = canvas.toDataURL('image/png');
        });
    }
})();
