<?php
/**
 * @Author: Kenyon Haliwell
 * @Date Created: 11/13/13
 * @Date Modified: 11/21/13
 * @Purpose: Controller for pay periods
 * @Version: 1.0
 */

/**
 * @Purpose: Controller for pay periods
 */
class timeclock_payperiod extends controller {
    /**
     * @Purpose: This function is used to determin if the user is logged in or not
     */
    protected function is_logged_in() {
        return $this->logged_in->status();
    }
    
    /**
     * @Purpose: Default function to be run when class is called
     */
    public function index() {}
    
    /**
     * @Purpose: Used to punch in and out using the RESTFUL API
     */
    public function tx($uid) {
        $this->pay_period = $this->load_model('payPeriod', $this->system_di->config->timeclock_subdirectories);
        
        $employee = $this->system_di->db->query("SELECT * FROM `employees` WHERE `employee_uid`=:uid", array(
            ':uid' => $uid
        ));
        
        $response = $this->pay_period->employee_punch($employee[0]['employee_id']);
        
        if ('Error' == $response) {
            $this->system_di->template->response = $response;
        } else {
            $this->system_di->template->response = $employee[0]['employee_firstname'] . ', ' . $response[0] . ', ' . $response[1]['date'] . ', ' . $response[1]['time'];
        }
        
        $this->system_di->template->parse($this->system_di->config->timeclock_subdirectories . '_payperiod_response');
    }
    
    /**
     * @Purpose: Used to send data back using the RESTFUL API
     */
    public function rx($uid, $data, $pay_period='current') {
        $this->pay_period = $this->load_model('payPeriod', $this->system_di->config->timeclock_subdirectories);
        $employee = $this->system_di->db->query("SELECT * FROM `employees` WHERE `employee_uid`=:uid", array(
            ':uid' => $uid
        ));
        $pay_period_query = $this->system_di->db->query("SELECT `time`,`operation` FROM `employee_punch` WHERE `employee_id`=:employee_id ORDER BY `employee_punch_id` DESC", array(
            ':employee_id' => (int) $employee[0]['employee_id']
        ));
        
        $pay_period = $this->pay_period->get_pay_period();
        $response = 'Error';
        
        if (empty($employee)) {
            $this->system_di->template->response = $response;
            $this->system_di->template->parse($this->system_di->config->timeclock_subdirectories . '_payperiod_response');
            
            return False;
        }

        switch ($data) {
            case 'employee_name':
                $response = $employee[0]['employee_firstname'] . ' ' . $employee[0]['employee_lastname'];
                break;
            case 'last_op':
                $response = $pay_period_query[0]['operation'];
                break;
            case 'last_time':
                $response = date('g:ia', $pay_period_query[0]['time']);
                break;
            case 'total_hours':
                $response = $this->pay_period->total_hours_for_pay_period($employee[0]['employee_id'], $pay_period[0][0]);
                break;
            default:
                $response = NULL;
        }
        
        $this->system_di->template->response = $response;
        $this->system_di->template->parse($this->system_di->config->timeclock_subdirectories . '_payperiod_response');
        return True;
    }
    
    /**
     * @Purpose: Used to create a print friendly version of a pay period
     */
    public function print_friendly($employee_id, $pay_period) {
        $renderPage = $this->load_model('renderPage', $this->system_di->config->timeclock_subdirectories);
        $this->logged_in = $this->load_model('loggedIn', $this->system_di->config->timeclock_subdirectories);
        $this->pay_period = $this->load_model('payPeriod', $this->system_di->config->timeclock_subdirectories);
        $pay_period = $this->pay_period->get_pay_period($pay_period);
        
        $employee = $this->system_di->db->query("SELECT `employee_firstname`,`employee_lastname` FROM `employees` WHERE `employee_id`=:employee_id", array(
            ':employee_id' => (int) $employee_id
        ));
        
        if ($this->is_logged_in()) {
            $this->system_di->template->title = 'TimeClock | Printer Friendly Timecard';
            $this->system_di->template->pay_period_table = $this->pay_period->generate_pay_period_table($employee_id, $pay_period[0][0]);
            $this->system_di->template->name = $employee[0]['employee_firstname'] . ' ' . $employee[0]['employee_lastname'];
            $this->system_di->template->monday = date('m/d/y', $pay_period[0][0]);
            $this->system_di->template->sunday = date('m/d/y', $pay_period[1][0]);
            $this->system_di->template->total_hours = $this->pay_period->total_hours_for_pay_period($employee_id, $pay_period[0][0]);
        } else {
            header('Location: ' . $this->system_di->config->timeclock_root);
        }
        
        $renderPage->parse('payperiod_print');
    }
}//End timeclock_payperiod

//End File
