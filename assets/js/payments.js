var REDCapPayment = {}
REDCapPayment = {
    redirectURL : '',
    paymentURL: '',
    init: function () {
        var elem = $(document).find("#payment-button")

        if (elem !== undefined){
            console.log("element found")
            console.log(this.paymentURL)

            elem.attr("href", this.paymentURL)
        }
    }
}