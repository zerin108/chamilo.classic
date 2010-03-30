<?php
/* For licensing terms, see /license.txt */

/**
* Layout (principal view) used for structuring other views  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_description
*/

// protect script
api_block_anonymous_users();

// Header
Display :: display_header('');

// Display
echo $content;

// Footer
Display :: display_footer();
?>