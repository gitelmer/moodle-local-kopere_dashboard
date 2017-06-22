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

namespace local_kopere_dashboard\report\custom\course\reports;

use local_kopere_dashboard\html\DataTable;
use local_kopere_dashboard\html\TableHeaderItem;
use local_kopere_dashboard\report\custom\ReportInterface;
use local_kopere_dashboard\util\Header;

class CourseCompletePercent implements ReportInterface {

    public $reportName = 'Progresso com percentual de conclusão';

    /**
     * @return string
     */
    public function name() {
        return $this->reportName;
    }

    /**
     * @return boolean
     */
    public function isEnable() {
        return true;
    }

    /**
     * @return void
     */
    public function generate() {
        $courseid = optional_param('courseid', 0, PARAM_INT);
        if ($courseid == 0) {
            $this->listCourses();
        } else {
            $this->createReport();
        }
    }

    private function listCourses() {
        echo '<h3>Selecione o curso para gerar o relatório</h3>';

        $table = new DataTable();
        $table->addHeader('#', 'id', TableHeaderItem::TYPE_INT, null, 'width: 20px');
        $table->addHeader('Nome do Curso', 'fullname');
        $table->addHeader('Nome curto', 'shortname');
        $table->addHeader('Visível', 'visible', TableHeaderItem::RENDERER_VISIBLE);
        $table->addHeader('Nº alunos inscritos', 'inscritos', TableHeaderItem::TYPE_INT, null, 'width:50px;white-space:nowrap;');

        $table->setAjaxUrl('Courses::loadAllCourses');
        $table->setClickRedirect('Reports::loadReport&type=course&report=CourseCompletePercent&courseid={id}', 'id');
        $table->printHeader();
        $table->close();
    }

    /**
     * @return void
     */
    public function createReport() {
        global $DB;

        $courseid = optional_param('courseid', 0, PARAM_INT);
        if ($courseid == 0) {
            Header::notfound('CourseID inválido!');
        }

        $reportSql
            = "SELECT ue.id,
                      u.id AS userid,
                      u.firstname, u.lastname, u.alternatename, u.middlename, u.lastnamephonetic, u.firstnamephonetic,
                      u.email,

                      c.id AS courseid,
                      c.fullname,
                      ue.timecreated,

                      IFNULL((SELECT COUNT(gg.finalgrade)
                        FROM {grade_grades}  gg
                        JOIN {grade_items}  gi ON gg.itemid=gi.id
                        WHERE gi.courseid=c.id
                         AND gg.userid=u.id
                         AND gi.itemtype='mod'
                         GROUP BY u.id,c.id),'0') AS 'activities_completed',

                      IFNULL((SELECT COUNT( gi.itemname )
                        FROM {grade_items}  gi
                        WHERE gi.courseid = c.id
                         AND gi.itemtype='mod'), '0') AS 'activities_assigned',

                      -- /*If activities_completed = activities_assigned, show date of last log entry. Otherwise,
                      --   show percentage complete. If activities_assigned = 0, show 'n/a'.--*/
                      (
                          SELECT IF(`activities_assigned`!='0', (
                              SELECT IF( `activities_completed` = `activities_assigned`,
                              (
                                  SELECT CONCAT('100% completo',FROM_UNIXTIME(MAX(log.time),'%m/%d/%Y'))
                                    FROM {log} log
                                   WHERE log.course = c.id
                                     AND log.userid = u.id
                              ),
                              -- /*--Percent completed--*/
                              (
                                  SELECT CONCAT(IFNULL(ROUND((`activities_completed`)/(`activities_assigned`)*100,0), '0'),'% complete')))), 'n/a')
                      ) AS 'course_completed'

                    FROM {user} u
                    JOIN {user_enrolments}  ue  ON ue.userid = u.id
                    JOIN {enrol}            e   ON e.id = ue.enrolid
                    JOIN {course}           c   ON c.id = e.courseid
                    JOIN {context}          ctx ON ctx.instanceid = c.id AND ctx.instanceid = c.id
                    JOIN {role_assignments} ra  ON ra.contextid = ctx.id AND ra.userid = u.id
                    JOIN {role}             r   ON r.id = e.roleid

                    WHERE ra.roleid = 5
                      AND c.id      = :courseid
                      -- AND c.visible = 1
                      -- AND ue.status = 0
                  GROUP BY u.id, c.id";

        $reports = $DB->get_records_sql($reportSql, array('courseid' => $courseid));

        $report = array();
        foreach ($reports as $item) {

            $item->userfullname = fullname($item);

            $report[] = $item;
        }

        $table = new DataTable();

        $table->addInfoHeader('Dados do Aluno', 3);
        $table->addInfoHeader('Dados do Curso', 5);

        $table->addHeader('#', 'userid', TableHeaderItem::TYPE_INT, null, 'width: 20px');
        $table->addHeader('Nome', 'userfullname');
        $table->addHeader('E-mail', 'email');
        $table->addHeader('Curso', 'fullname');
        $table->addHeader('Data da inscrição', 'timecreated', TableHeaderItem::RENDERER_DATE);
        $table->addHeader('Atividades Concluídas', 'activities_completed', TableHeaderItem::TYPE_INT);
        $table->addHeader('Atividades Atribuídas', 'activities_assigned', TableHeaderItem::TYPE_INT);
        $table->addHeader('Completo', 'course_completed');

        $table->setIsExport(true);
        $table->setClickRedirect('Users::details&userid={userid}', 'userid');
        $table->printHeader('', false);
        $table->setRow($report);
        $table->close(false);

    }

    public function listData() {

    }
}