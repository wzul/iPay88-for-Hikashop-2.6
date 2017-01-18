<?php
//Prevent from direct access
defined('_JEXEC') or die('Restricted access');
?>
<!-- Here is the ending page, called at the end of the checkout, just before the user is redirected to the payment platform -->
<div class="hikashop_ipay88_end" id="hikashop_ipay88_end">
    <!-- Waiting message -->
    <span id="hikashop_ipay88_end_message" class="hikashop_ipay88_end_message"><?php
        echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name) . '<br/>' . JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED');
        ?></span>
    <span id="hikashop_ipay88_end_spinner" class="hikashop_ipay88_end_spinner">
        <img src="<?php echo HIKASHOP_IMAGES . 'spinner.gif'; ?>" />
    </span>
    <br/>

    <!-- To send all requiered information, a form is used. Hidden input are setted with all variables, and the form is auto submit with a POST method to the payment plateform URL -->
    <form id="hikashop_ipay88_form" name="hikashop_ipay88_form" action="<?php echo $this->vars['URL']; ?>" method="post" name="ePayment">
        <div id="hikashop_ipay88_end_image" class="hikashop_ipay88_end_image">
            <input id="hikashop_ipay88_button" class="btn btn-primary" type="submit" value="<?php echo JText::_('PAY_NOW'); ?>" name="" alt="<?php echo JText::_('PAY_NOW'); ?>" />
        </div>
        <?php
        foreach ($this->vars as $name => $value) {
            echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars((string) $value) . '" />';
        }

        $doc = JFactory::getDocument();
        // We add some javascript code
        $doc->addScriptDeclaration("window.hikashop.ready(function(){ document.getElementById('hikashop_ipay88_form').submit(); });");
        JRequest::setVar('noform', 1);
        ?>
    </form>
</div>
