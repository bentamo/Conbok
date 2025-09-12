<?php
function back_to_view_events() {
    return '
    <a href="http://localhost/conbook/view-events/" 
       style="display:inline-block;
              padding:10px 20px;
              border-radius:30px;
              background:linear-gradient(135deg,rgb(255,75,43) 0%,rgb(125,63,255) 100%);
              font-family:\'Inter\',sans-serif;
              font-weight:500;
              color:#fff;
              text-decoration:none;
              text-align:center;"
    >← Back to View Events</a>';
}
add_shortcode('btn-back-to-view-events', 'back_to_view_events');
