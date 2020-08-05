<?php
/**
 * User: Eduardo Kraus
 * Date: 21/03/2020
 * Time: 10:17
 */

namespace local_kopere_dashboard\task;


use local_kopere_dashboard\performancemonitor;

class performance extends \core\task\scheduled_task {

    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('crontask_performance', 'local_kopere_dashboard');
    }

    /**
     * @throws \coding_exception
     */
    public function execute() {
        if (!performancemonitor::CRON) {
            return;
        }

        $time = time();
        $this->add_data($time, 'cpu',     performancemonitor::cpu(true));
        $this->add_data($time, 'memory',  performancemonitor::memory(true));
        $this->add_data($time, 'disk',    performancemonitor::disk_moodledata(true)); //I changed the table (field: float to varchar) and it works. disk_moodledata(true) return a string --> "## GB"
        $this->add_data($time, 'average', performancemonitor::load_average(true));
        $this->add_data($time, 'users', performancemonitor::online());
    }

    private function add_data($time, $type, $value) {
        global $DB;

        $kopere_dashboard_performance = (object)array(
            'time' => $time,
            'type' => $type,
            'value' => $value
        );

        try {
            $DB->insert_record("kopere_dashboard_performance", $kopere_dashboard_performance);
            return true;
        } catch (\dml_exception $e) {
            return false;
        }
    }
}
