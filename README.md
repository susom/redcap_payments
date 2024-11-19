# REDCap Payments

This module allows Research teams to set up a Redirect-based Payment Integration to enable participants to make payments in the Payment Procesor environment. Then redirect participant back to REDCap after payment is complete. 

Add following button as a descriptive field. Then Define your Branching logic to display the field. 
```
<div class="container">
  <div class="row">
    <div class="col text-center">
<a class="btn btn-primary" id="payment-button" href="#" role="button">Pay Here</a>
    </div>
  </div>
</div>
```
