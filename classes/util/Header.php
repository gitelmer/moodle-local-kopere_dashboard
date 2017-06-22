<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_kopere_dashboard
 * @copyright  2017 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_dashboard\util;

class Header {
    public static function location($url, $isDie = true) {
        ob_clean();
        header('Location: ?' . $url);

        if ($isDie) {
            die ('Redirecionando para ?' . $url);
        }
    }

    public static function reload($isDie = true) {
        ob_clean();
        $url = $_SERVER['QUERY_STRING'];

        header('Location: ?' . $url);
        if ($isDie) {
            die ('Redirecionando para ?' . $url);
        }
    }

    public static function notfoundNull($param, $printText = false) {
        if ($param == null) {
            self::notfound($printText);
        }
    }

    public static function notfound($printText = false) {
        global $CFG;

        if (!AJAX_SCRIPT) {
            header('HTTP/1.0 404 Not Found');
        }

        DashboardUtil::startPage('Erro');

        echo '<div class="element-box text-center page404">
                  <img width="200" height="200" src="' . $CFG->wwwroot . '/local/kopere_dashboard/assets/dashboard/img/404.svg">
                  <h2>OOPS!</h2>
                  <div class="text404 text-danger">' . $printText . '</div>
                  <p>
                      <a href="#" onclick="window.history.back();return false;"
                         class="btn btn-primary">Voltar</a>
                  </p>
              </div>';

        DashboardUtil::endPage();
        die();
    }
}