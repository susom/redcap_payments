<?php

namespace Stanford\REDCapPayments;

/** @var \Stanford\REDCapPayments\REDCapPayments $this */

try {
    ?>
    <script src="<?php echo $this->getUrl('assets/js/payments.js') ?>"></script>
    <script>
        REDCapPayment.paymentURL = "<?php echo $this->getPaymentProcessorURL($this->record, urlencode($this->getUrl('pages/callback', true, true) . "&pid=" . $this->getProjectId() . "&" . \REDCap::getRecordIdField() . "=" . $this->record . "&event_id=" . $this->eventID))?>"
            window.addEventListener("load",
                function () {
                    setTimeout(function () {
                        REDCapPayment.init();
                    }, 100)
                }
                , true);
    </script>
    <?php

} catch (\Exception $e) {
    echo $e->getMessage();
}