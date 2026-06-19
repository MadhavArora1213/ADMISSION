<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editor Diagnostic</title>
    <!-- NO cache, so browser always loads fresh -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2>Trumbowyg Diagnostic</h2>
    <div id="status" style="background:#fef3c7; padding:10px; margin-bottom:15px; font-weight:bold;">Loading...</div>
    <textarea id="content_body" rows="10" style="width:100%;"></textarea>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/table/ui/trumbowyg.table.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/table/trumbowyg.table.min.js"></script>
    <script>
    window.onerror = function(msg, url, line, col, err) {
        var s = document.getElementById('status');
        s.style.background = '#fecaca';
        s.style.color = '#7f1d1d';
        s.innerHTML = 'JS ERROR: ' + msg + '<br>File: ' + (url||'?').split('/').pop() + ':' + line + '<br>' + (err && err.stack ? err.stack : '');
    };
    </script>
    <script>
    $(function(){
        var s = document.getElementById('status');
        try {
            if (typeof $.fn.trumbowyg !== 'function') {
                s.style.background='#fecaca'; s.style.color='#7f1d1d';
                s.innerHTML = 'FAIL: trumbowyg.min.js did not load (CDN blocked?).';
                return;
            }
            $('#content_body').trumbowyg({
                btns: [
                    ['formatting'],
                    ['strong','em'],
                    ['insertImage'],
                    ['table'],
                    ['fullscreen']
                ],
                plugins: { table: { rows: 5, columns: 5 } }
            });
            s.style.background = '#dcfce7';
            s.style.color = '#14532d';
            s.innerHTML = 'OK: Editor loaded. Try the TABLE button above -> click cells to pick size.';
            console.log('table plugin registered?', !!$.trumbowyg.plugins.table);
            console.log('table btn exists?', !!$('.trumbowyg-table-button').length);
        } catch(e) {
            s.style.background='#fecaca'; s.style.color='#7f1d1d';
            s.innerHTML = 'ERROR: ' + e.message;
            console.error(e);
        }
    });
    </script>
</body>
</html>
