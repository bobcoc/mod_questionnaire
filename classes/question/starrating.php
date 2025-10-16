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

namespace mod_questionnaire\question;

/**
 * Star Rating question type class for questionnaire.
 * Based on Rate question type to support multiple rating items.
 *
 * @package    mod_questionnaire
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class starrating extends question {

    /**
     * The class constructor
     * @param int $id
     * @param \stdClass $question
     * @param \context $context
     * @param array $params
     */
    public function __construct($id = 0, $question = null, $context = null, $params = []) {
        // Set default length to 5 stars (like Rate does with $this->length)
        $this->length = 5;
        
        parent::__construct($id, $question, $context, $params);
    }

    /**
     * Each question type must define its response class.
     * @return object The response object based off of questionnaire_response_base.
     */
    protected function responseclass() {
        return '\\mod_questionnaire\\responsetype\\rank';
    }

    /**
     * Short name for this question type - no spaces, etc..
     * @return string
     */
    public function helpname() {
        return 'starrating';
    }

    /**
     * Return true if the question has choices.
     */
    public function has_choices() {
        return true;
    }

    /**
     * Override and return a form template if provided.
     * @return boolean | string
     */
    public function question_template() {
        return 'mod_questionnaire/question_starrating';
    }

    /**
     * Override and return a response template if provided.
     * @return boolean | string
     */
    public function response_template() {
        return 'mod_questionnaire/response_starrating';
    }

    /**
     * True if question type supports feedback options.
     */
    public function supports_feedback() {
        return true;
    }

    /**
     * True if the question supports feedback and has valid settings.
     */
    public function valid_feedback() {
        return $this->supports_feedback() && $this->has_choices() && $this->required() && !empty($this->name);
    }

    /**
     * Get the maximum score possible for feedback if appropriate.
     * @return int | boolean
     */
    public function get_feedback_maxscore() {
        if ($this->valid_feedback()) {
            $nbchoices = count($this->choices);
            // Max score = number of items * max stars
            return $nbchoices * $this->length;
        }
        return false;
    }

    /**
     * Return the context tags for the star rating question display.
     * @param \mod_questionnaire\responsetype\response\response $response
     * @param array $dependants
     * @param boolean $blankquestionnaire
     * @return object The star rating question context tags.
     */
    protected function question_survey_display($response, $dependants = [], $blankquestionnaire = false) {
        global $PAGE;

        // Add required CSS
        $PAGE->requires->css('/mod/questionnaire/styles_starrating.css');

        $choicetags = new \stdClass();
        $choicetags->qelements = new \stdClass();
        $choicetags->qelements->maxstars = $this->length;
        $choicetags->qelements->rows = [];

        $disabled = $blankquestionnaire ? ' disabled="disabled"' : '';

        foreach ($this->choices as $cid => $choice) {
            $rowobj = new \stdClass();
            $str = 'q' . $this->id . '_' . $cid;
            $rowobj->name = $str;
            $rowobj->content = format_text($choice->content, FORMAT_HTML, ['noclean' => true]);
            $rowobj->choiceid = $cid;
            $rowobj->maxstars = $this->length; // 添加到每行以便访问

            // Get current value if exists
            $currentvalue = 0;
            if (isset($response->answers[$this->id][$cid])) {
                $currentvalue = intval($response->answers[$this->id][$cid]->value);
            }
            $rowobj->value = $currentvalue;
            $rowobj->disabled = !empty($disabled);

            // Generate stars data
            $rowobj->stars = [];
            for ($i = 1; $i <= $this->length; $i++) {
                $star = new \stdClass();
                $star->value = $i;
                $star->selected = ($i <= $currentvalue);
                $rowobj->stars[] = $star;
            }

            $choicetags->qelements->rows[] = $rowobj;
        }

        return $choicetags;
    }

    /**
     * Return the context tags for the star rating response display.
     * @param \mod_questionnaire\responsetype\response\response $response
     * @return \stdClass The star rating question response context tags.
     */
    protected function response_survey_display($response) {
        $resptags = new \stdClass();
        $resptags->rows = [];
        $resptags->maxstars = $this->length;

        foreach ($this->choices as $cid => $choice) {
            $rowobj = new \stdClass();
            $rowobj->content = format_text($choice->content, FORMAT_HTML, ['noclean' => true]);

            // Get the star rating value
            $value = 0;
            if (isset($response->answers[$this->id][$cid])) {
                $value = intval($response->answers[$this->id][$cid]->value);
            }
            $rowobj->value = $value;

            // Generate stars display
            $rowobj->stars = [];
            for ($i = 1; $i <= $this->length; $i++) {
                $star = new \stdClass();
                $star->filled = ($i <= $value);
                $rowobj->stars[] = $star;
            }

            $resptags->rows[] = $rowobj;
        }

        return $resptags;
    }

    /**
     * Check question's form data for complete response.
     * @param \stdClass $responsedata The data entered into the response.
     * @return boolean
     */
    public function response_complete($responsedata) {
        if (!is_a($responsedata, 'mod_questionnaire\\responsetype\\response\\response')) {
            $response = \mod_questionnaire\responsetype\response\response::response_from_webform($responsedata, [$this]);
        } else {
            $response = $responsedata;
        }

        // Create an array of answers by choiceid
        $answers = [];
        if (isset($response->answers[$this->id])) {
            foreach ($response->answers[$this->id] as $answer) {
                $answers[$answer->choiceid] = $answer;
            }
        }

        $answered = true;
        $num = 0;
        $nbchoices = count($this->choices);

        foreach ($this->choices as $cid => $choice) {
            // Count only valid star ratings (1 to max stars)
            $num += (isset($answers[$cid]) && ($answers[$cid]->value > 0) && ($answers[$cid]->value <= $this->length));
        }

        if ($num == 0 && $this->required()) {
            $answered = false;
        }

        return $answered;
    }

    /**
     * Check question's form data for valid response.
     * @param \stdClass $responsedata The data entered into the response.
     * @return boolean
     */
    public function response_valid($responsedata) {
        if (!is_a($responsedata, 'mod_questionnaire\\responsetype\\response\\response')) {
            $response = \mod_questionnaire\responsetype\response\response::response_from_webform($responsedata, [$this]);
        } else {
            $response = $responsedata;
        }

        // Create an array of answers by choiceid
        $answers = [];
        if (isset($response->answers[$this->id])) {
            foreach ($response->answers[$this->id] as $answer) {
                $answers[$answer->choiceid] = $answer;
            }
        }

        // Validate that all ratings are within valid range
        foreach ($answers as $answer) {
            if ($answer->value < 0 || $answer->value > $this->length) {
                return false;
            }
        }

        return parent::response_valid($responsedata);
    }

    /**
     * Return the length form element.
     * @param \MoodleQuickForm $mform
     * @param string $helptext
     */
    protected function form_length(\MoodleQuickForm $mform, $helptext = '') {
        $lengthoptions = [];
        for ($i = 3; $i <= 10; $i++) {
            $lengthoptions[$i] = $i;
        }
        $mform->addElement('select', 'length', get_string('maxstars', 'questionnaire'), $lengthoptions);
        $mform->setDefault('length', 5);
        $mform->addHelpButton('length', 'maxstars', 'questionnaire');
        return $mform;
    }

    /**
     * Return the form precision (hidden for star rating).
     * @param \MoodleQuickForm $mform
     * @param string $helptext
     * @return \MoodleQuickForm
     */
    protected function form_precise(\MoodleQuickForm $mform, $helptext = '') {
        return question::form_precise_hidden($mform);
    }

    /**
     * Override to make choices field optional (not required).
     * @param \MoodleQuickForm $mform
     * @return string
     */
    protected function form_choices(\MoodleQuickForm $mform) {
        if ($this->has_choices()) {
            $numchoices = count($this->choices);
            $allchoices = '';
            foreach ($this->choices as $choice) {
                if (!empty($allchoices)) {
                    $allchoices .= "\n";
                }
                $allchoices .= $choice->content;
            }

            $helpname = $this->helpname();

            $mform->addElement('html', '<div class="qoptcontainer">');
            $options = ['wrap' => 'virtual', 'class' => 'qopts'];
            $mform->addElement('textarea', 'allchoices', get_string('possibleanswers', 'questionnaire'), $options);
            $mform->setType('allchoices', PARAM_RAW);
            // 注意：这里没有添加 'required' 规则，使它成为可选字段
            $mform->addHelpButton('allchoices', $helpname, 'questionnaire');
            $mform->addElement('html', '</div>');
            $mform->addElement('hidden', 'num_choices', $numchoices);
            $mform->setType('num_choices', PARAM_INT);
        }
        return $allchoices ?? '';
    }

    /**
     * Override this function for question specific choice preprocessing.
     * Allow empty choices field, similar to Rate question type.
     * @param \stdClass $formdata
     * @return bool
     */
    protected function form_preprocess_choicedata($formdata) {
        if (empty($formdata->allchoices)) {
            // Add dummy blank space character for empty value.
            $formdata->allchoices = " ";
        }
        return true;
    }

    /**
     * True if question provides mobile support.
     * @return bool
     */
    public function supports_mobile() {
        return true;
    }

    /**
     * Return mobile question display data.
     * @param int $qnum
     * @param bool $autonum
     * @return \stdClass
     */
    public function mobile_question_display($qnum, $autonum = false) {
        $mobiledata = parent::mobile_question_display($qnum, $autonum);
        $mobiledata->isstarrating = true;
        $mobiledata->maxstars = $this->length;
        return $mobiledata;
    }

    /**
     * Return mobile question choices display.
     * @return array
     */
    public function mobile_question_choices_display() {
        $choices = [];
        $cnum = 0;

        foreach ($this->choices as $choiceid => $choice) {
            $choice->choice_id = $choiceid;
            $choice->id = $choiceid;
            $choice->question_id = $this->id;
            $choice->fieldkey = $this->mobile_fieldkey($choiceid);
            $choice->min = 0;
            $choice->max = $this->length;
            $choices[$cnum] = $choice;
            $cnum++;
        }

        return $choices;
    }

    /**
     * Return the mobile response data.
     * @param \mod_questionnaire\responsetype\response\response $response
     * @return array
     */
    public function get_mobile_response_data($response) {
        $resultdata = [];
        if (isset($response->answers[$this->id])) {
            foreach ($response->answers[$this->id] as $answer) {
                $resultdata[$this->mobile_fieldkey($answer->choiceid)] = $answer->value;
            }
        }
        return $resultdata;
    }
}
