<?php
/* Cavern Edition
   modified by t510599 at 2019/05/30
*/
/*
<Secret Blog>
Copyright (C) 2012-2017 太陽部落格站長 Secret <http://gdsecret.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, version 3.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Also add information on how to contact you by electronic and paper mail.

  If your software can interact with users remotely through a computer
network, you should also make sure that it provides a way for users to
get its source.  For example, if your program is a web application, its
interface could display a "Source" link that leads users to an archive
of the code.  There are many ways you could offer source, and different
solutions will be better for different programs; see section 13 for the
specific requirements.

  You should also get your employer (if you work as a programmer) or school,
if any, to sign a "copyright disclaimer" for the program, if necessary.
For more information on this, and how to apply and follow the GNU AGPL, see
<http://www.gnu.org/licenses/>.
*/

class View {
    private $master_content;
    private $nav_content;
    private $sidebar_content;
    private $message = array();
    private $script = array();
    private $title;
    private $part;

    public function __construct($master,$nav,$sidebar,$title,$part) {
        $this->load($master,$nav,$sidebar);
        $this->title = $title;
        $this->part = $part;
        ob_start();
    }

    private function load($master,$nav,$sidebar) {
        ob_start();
        include($master);
        $this->master_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include($nav);
        $this->nav_content = ob_get_contents();
        ob_end_clean();
        
        if ($sidebar!='') {
            ob_start();
            include($sidebar);
            $this->sidebar_content = ob_get_contents();
            ob_end_clean();
        }
    }
    
    public function add_script($src) {
        $this->script[] = "<script src=\"$src\"></script>";
    }

    public function add_script_source($source)  {
        $this->script[] = "<script>$source</script>";
    }

    public function show_message($class, $msg) {
        $this->message[] = "<div class=\"ts $class message\"><p>$msg</p></div>";
    }
    
    public function render() {
        $content = ob_get_contents();
        ob_end_clean();
        
        echo strtr($this->master_content, array(
            '{title}' => $this->title,
            '{part}' => $this->part,
            '{script}' => join(PHP_EOL, $this->script),
            '{nav}' => $this->nav_content,
            '{sidebar}' => $this->sidebar_content,
            '{message}' => join(PHP_EOL, $this->message),
            '{content}' => $content
        ));
        @ob_flush();
        flush();
    }
};