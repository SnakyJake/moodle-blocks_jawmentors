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
 * Mentors block.
 *
 * @package    block_jawmentors
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @copyright  2021 onwards Jakob Heinemann (http://www.jakobheinemann.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_jawmentors extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_jawmentors');
    }
    
    function has_config() {
        return true;
    }
    
    function applicable_formats() {
        return array('all' => true, 'tag' => false);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? $this->config->title : get_string('newmentorsblock', 'block_jawmentors');
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT,$PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }
		
	$timetoshowusers = 300;
	$timefrom = 100 * floor((time()-$timetoshowusers) / 100);

        $this->content = new stdClass();
        
        if(!$visible_roleid = get_config('block_jawmentors', 'visible_roleid')){
            $visible_roleid = 40;
        }

        // get all the mentors, i.e. users you have a direct assignment to
        //$allusernames = get_all_user_name_fields(true, 'u');
		$allusernamefields = \core_user\fields::for_name()->get_sql('u')->selects;
        if ($usercontexts = $DB->get_records_sql("SELECT ra.userid, u.lastaccess $allusernamefields
                                                    FROM {context} c, {role_assignments} ra, {user} u
                                                   WHERE c.contextlevel = ?
                                                         AND c.instanceid = ?
														 AND c.instanceid != u.id
                                                         AND c.id = ra.contextid
                                                         AND ra.roleid = ?
                                                         AND ra.userid = u.id
                                                         AND u.suspended = 0
                                                   ORDER BY u.lastname", array(CONTEXT_USER, $USER->id, $visible_roleid))) {

            $this->content->text = '<ul>';
            foreach ($usercontexts as $usercontext) {
				$online = $usercontext->lastaccess > $timefrom;

                $this->content->text .= '<li style="clear:both;';
				if($online) {
					$this->content->text .= "list-style-image:url('".$OUTPUT->image_url('s/smiley')."');".'" title="online"';
				} else {
					$this->content->text .= "list-style-image:url('".$OUTPUT->image_url('t/stop')."');".'" title="offline"';
				}
				$this->content->text .= '><a href="'.$CFG->wwwroot.'/user/view.php?id='.$usercontext->userid.'&amp;course='.SITEID.'">'.fullname($usercontext).'</a>';
//				$this->content->text .= '><a href="'.$CFG->wwwroot.'/message/index.php?id='.$usercontext->userid.'">'.fullname($usercontext).'</a>';
                $this->content->text .= ' <a class="jawmentors-message-icon float-right" role="button" data-conversationid="0" data-userid="'.$usercontext->userid.'" class="btn" href="https://edu.jaw-moodle.de/message/index.php?id='.$usercontext->userid.'"><span><i class="icon fa fa-comment fa-fw iconsmall" title="Mitteilung" aria-label="Mitteilung"></i></span></a>';
				$this->content->text .= '</li>';
            }
            $this->content->text .= '</ul>';
        }

        $this->content->footer = '';
		
        $PAGE->requires->js_amd_inline(
            "require(['jquery', 'core/custom_interaction_events', 'core_message/message_drawer_helper'],
    function($, CustomEvents, MessageDrawerHelper) {
        var elementLocator = '[data-conversationid=\"0\"][data-userid]';
        CustomEvents.define($('body'), [CustomEvents.events.activate]);
        $('body').on(CustomEvents.events.activate, elementLocator, function(e, data) {
            e.preventDefault();
            data.originalEvent.preventDefault();
            MessageDrawerHelper.createConversationWithUser({buttonid:0,userid:parseInt($(e.currentTarget).attr('data-userid'))});
        });
    });"
        );

        return $this->content;
    }

    /**
     * Returns true if the block can be docked.
     * The mentors block can only be docked if it has a non-empty title.
     * @return bool
     */
    public function instance_can_be_docked() {
        return parent::instance_can_be_docked() && isset($this->config->title) && !empty($this->config->title);
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = !empty($this->config) ? $this->config : new stdClass();

        return (object) [
            'instance' => $configs,
            'plugin' => new stdClass(),
        ];
    }
}

