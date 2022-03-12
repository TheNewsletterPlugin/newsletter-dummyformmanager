<?php

class NewsletterDummyFormManagerAddon extends NewsletterFormManagerAddon {

    /**
     * @var NewsletterDummyFormManagerAddon
     */
    static $instance;

    function __construct($version) {
        self::$instance = $this;

        // Don't need to add a menu, it is managed by the base class
        $this->menu_title = 'Dummy Form Manager';
        $this->menu_description = 'Integration with your Dummy Form Manager';

        // Change the addon identifier with your own.
        // The __DIR__ is required to let the base cass load the admin files
        parent::__construct('dummyformmanager', $version, __DIR__);
    }

    function init() {
        parent::init();

        // Add here thre action to intercept the form submission of the form manager plugin.
        // Each plugin has a different action
        // You should set the corrent number of parameters available with the action and a priority
        add_action('the_form_submission', [$this, 'the_form_submission']);
    }

    /**
     * Just an example, refer to the documentation of the form manager plugin you're using.
     * This method must never end with a die() or a fatal error since it is inserted in the form
     * manager data flow.
     * 
     * @param mixed $data
     * @return type
     */
    public function the_form_submission($data) {

        // Rememer to NOT log sensitive data
        $logger = $this->get_logger();

        // Generic but unique identifier of the submitted form, usually passed via one of the action
        // parameters. The identifier is used to extract the mapping configuration for this specific form.
        $form_id = '...';

        // Extract the mapping options. For example, we can have into $form_options['name'] the 
        // information to extract the name from the submisison data (usually a field name or identifier)
        $form_options = $this->get_form_options($form_id);

        // This is an object made to collect all the subscription data and options, later
        // processed by Newsletter. See the TNP_Subscription and TNP_Subscription_Data
        // classes.
        $subscription = NewsletterSubscription::instance()->get_default_subscription();

        // First, get the email (without we cannot proceed). Is it mapped?
        if (!empty($form_options['email'])) {
            $email = '...';
            if (!NewsletterModule::is_email($email)) {
                $logger->error('The email field configured does not contain an email');
                return;
            }

            $subscription->data->email = $email;
        } else {
            $logger->error('The email field is not mapped');
            return;
        }

        // Is the first name field mapped?
        if (!empty($form_options['name'])) {
            $subscription->data->name = '...';
        }

        // Is the last name mapped? (ok, we know, surname is not correct... that cannot be changed)
        if (!empty($form_options['surname'])) {
            $subscription->data->surname = '...';
        }

        $subscription->data->add_lists($form_options['lists']);

        // A WP_Error can be returned and you can log it
        $res = NewsletterSubscription::instance()->subscribe2($subscription);

        if (is_wp_error($res)) {
            // The logger manages generic strings, objects, arrays and WP_Error instances. Just log and forget the details.
            $logger->error($res);
        }
    }

    /**
     * Returns a TNP_FormManager_Form representing all the fields of the form managed by
     * the 3rd party plugin.
     * 
     * @param string $form_id
     * @return \TNP_FormManager_Form
     */
    public function get_form($form_id) {

        $tnp_form = new TNP_FormManager_Form();
        $tnp_form->id = $form_id;

        // START CODING
        // 
        // Retrieve the form info from the form manager plugin
        // ...
        // Give it a title. For example, some form managers stores the form as WP_Post to the
        // form title is the post title...
        $tnp_form->title = '...';

        // For each field in the from, store a field ID and a field name, they will be
        // used in the mapping configuration. Usually you write a for loop, the code
        // below is only representative.

        $tnp_form->fields['FIELD ID'] = 'FIELD NAME';
        $tnp_form->fields['FIELD ID'] = 'FIELD NAME';
        $tnp_form->fields['FIELD ID'] = 'FIELD NAME';

        // END CODING

        return $tnp_form;
    }

    /**
     * Build a list of all forms available within the 3rd party from manager. The generated objects
     * are filled with only and ID and a title, just to build a list.
     * 
     * @return \TNP_FormManager_Form
     */
    public function get_forms() {

        // Extract the avaiable forms from the 3rd party plugin
        $forms = [];

        $list = [];

        foreach ($forms as $form) {
            // Build the onbject to represent the form
            $tnp_form = new TNP_FormManager_Form();

            // START CODING
            // 
            // Extract the form ID (from $form or with a custom 3rd party plugin function, we don't know)
            $tnp_form->id = '...';
            // Extract a form title from $form (presumably)
            $tnp_form->title = '...';

            // END CODING
            // Get the mapping configuration (could be missing, no problem)
            $settings = $this->get_form_options($tnp_form->id);

            // We assume the form is connected to Newsletter if the email field is mapped
            $tnp_form->connected = !empty($settings['email']);

            $list[] = $tnp_form;
        }

        return $list;
    }

}
