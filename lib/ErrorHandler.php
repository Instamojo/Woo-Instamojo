<?php

trait ErrorHandler {

    public function handle_validation_exception($e)
    {
        $this->log("Validation Exception Occured with response ".print_r($e->getResponse(), true));
        $errors_html = "<ul class=\"woocommerce-error\">\n\t\t\t";
        foreach ( $e->getErrors() as $error) {
            $errors_html .="<li>".$error."</li>";
        }
        $errors_html .= "</ul>";
        $this->prepare_and_send_error_json($errors_html);
    }

    public function handle_curl_exception($e)
    {
        $this->log("An error occurred on line " . $e->getLine() . " with message " .  $e->getMessage());
        $this->log("Traceback: " . (string)$e);
        $this->prepare_and_send_error_json("<ul class=\"woocommerce-error\">\n\t\t\t<li>" . $e->getMessage() . "</li>\n\t</ul>\n");
    }

    public function handle_exception($e)
    {
        $this->log("An error occurred on line " . $e->getLine() . " with message " .  $e->getMessage());
        $this->log("Traceback: " . $e->getTraceAsString());
        $this->prepare_and_send_error_json("<ul class=\"woocommerce-error\">\n\t\t\t<li>".$e->getMessage()."</li>\n\t</ul>\n");
    }

    public function handle_error($error_message)
    {
        $this->log($error_message);
        $this->prepare_and_send_error_json("<ul class=\"woocommerce-error\">\n\t\t\t<li>$error_message</li>\n\t</ul>\n");
    }
    
    private function prepare_and_send_error_json($errors_html)
    {
        $json = array(
            "result"=>"failure",
            "messages"=>$errors_html,
            "refresh"=>"false",
            "reload"=>"false"
        );
        die(json_encode($json));
    }
}
