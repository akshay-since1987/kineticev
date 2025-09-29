<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php';
require_once __DIR__ . '/components/layout.php';

$preload_images = [
    "/-/images/new/red/000144.png",
    "/-/images/new/black/000044.png"
];

startLayout("Kinetic Watts and Volts Shaping Tomorrow with Electric Power", [
    'preload_images' => $preload_images,
    'description' => 'Kinetic Watts and Volts combines decades of expertise with modern EV technology delivering innovative and reliable electric vehicle solutions in India.',
    'canonical' => 'https://kineticev.in/about-us'
]);
?>
<div class="faq-page">
    <section class="intro">
        <!-- <a href="#" onclick="window.history.back();" class="back-arrow">
            <i class="fa fa-arrow-left"></i>
        </a> -->
        <div class="container title-section-wrapper">
            <div class="page-title-section">
                <div class="intro-heading">
                    <h2>FAQs</h2>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-text">
        <div class="faq-text container">
            <div class="faq-group">
                <h2>About DX and DX+</h2>
                <details class="faq-item">
                    <summary class="faq-question">What is the difference between DX and DX+?</summary>
                    <div class="faq-answer">
                        The DX comes with Bluetooth connectivity and an off-board charger, and is available in two
                        classic colours – Black and White. The DX+ offers Bluetooth connectivity, advanced telematics,
                        an on-board charger for added convenience, and is available in five colour variants – Red, Blue,
                        Silver, Black and White.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What is MyKiney and how does it work?</summary>
                    <div class="faq-answer">
                        MyKiney is your scooter's friendly co-pilot, always keeping you updated and at ease, with 16
                        languages for personalization, functional alerts, safety alerts, personalized greetings, service
                        reminders, voice navigation and more!
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What are Easy Key, Easy Charge, and Easy Flip?</summary>
                    <div class="faq-answer">
                        <p><strong>Easy Key:</strong> Easy access to your Kinetic with passcode access. It's truly keyless.
                        You can update your passcode from your phone, no need to find or keep keys. (Bluetooth keyfob
                        available as an accessory).</p>
                        <p><strong>Easy Charge: </strong>It eliminates the hassle of carrying an off-board charger that
                        eats into your storage space. Available only on the DX+, Easy Charge is a retractable charging
                        cable feature that allows you to simply pull out the cable, plug in and charge your Kinetic
                        DX+.</p>
                        <p><strong>Easy Flip: </strong>You can access the pillion footrest without bending. Just Flip’em at
                        the press of a button on the Handlebar.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">When will my DX/DX+ be delivered?</summary>
                    <div class="faq-answer">
                        Vehicle deliveries will be carried out in phases. Our dedicated team will contact customers
                        directly to share the delivery schedule prior to handover.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">In what colours can I buy the DX and DX+?</summary>
                    <div class="faq-answer">
                        The DX+ is available in five colours – Blue, Black, Red, White, and Silver – whilst the DX is
                        offered in two colours – Black and White.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Can I change the variant for my existing order? How do I do that?
                    </summary>
                    <div class="faq-answer">
                        Yes, you may change your variant. For online bookings, please send a written request to our
                        Customer Care team and share a copy with your dealership to update your order.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What is the service interval?</summary>
                    <div class="faq-answer">
                        The first service is due at 1,000 km or 1 month (whichever comes first from the date of sale).
                        Subsequent services are required every 6 months or 6,000 km, whichever comes first.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">How much warranty do I get on the battery?</summary>
                    <div class="faq-answer">
                        <p>The battery is covered under the following warranty options:</p>
                        <ul>
                            <li>
                                <strong>Standard Warranty:</strong> 3 years or 50,000 km
                            </li>
                            <li>
                                <strong>Extended Warranty (Option 1):</strong> An additional 2 years or 20,000 km
                            </li>
                            <li>
                                <strong>Extended Warranty (Option 2):</strong> Up to 6 years or 50,000 km in total
                            </li>
                        </ul>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">How long does it take to charge the DX/DX+?</summary>
                    <div class="faq-answer">
                        It takes approximately 4 hours to charge the DX/DX+ from 0% to 100%.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">How much weight can the Kinetic EV carry?</summary>
                    <div class="faq-answer">
                        The Kinetic EV can comfortably carry a payload of up to 150 kg (rider, pillion, and load combined).
                    </div>
                </details>

            </div>

            <div class="faq-group">
                <h2>About Purchase</h2>
                <details class="faq-item">
                    <summary class="faq-question">How do I Book the DX / DX+?</summary>
                    <div class="faq-answer">
                        You can purchase the DX or DX+ easily through our official website, <a href="https://www.kineticev.in/">kineticev.in</a>. Simply select your preferred variant: DX or DX+ and choose from the available colour options (Black or Silver for the DX, and Red, Blue, White, Silver, or Black for the DX+). To complete your booking, pay the fully refundable booking fee of ₹1,000. Once confirmed, you will receive an email acknowledgement of your booking.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">How do I check the status of my order?</summary>
                    <div class="faq-answer">
                        To check the status of your DX or DX+ order, simply share your booking number via email at <a href="mailto:info@kineticev.in">info@kineticev.in</a> or contact our customer care team on <a href="tel:+918600096800">86000 96800</a>, and our team will provide you with the latest update on your order.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">When will my DX/DX+ be delivered?</summary>
                    <div class="faq-answer">
                        Vehicle deliveries will be carried out in phases. Our dedicated team will contact customers
                        directly to share the delivery schedule prior to handover.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Is the ex-showroom price inclusive of the PM E-DRIVE subsidy or exclusive of it?</summary>
                    <div class="faq-answer">
                        Yes, the ex-showroom price <strong>includes the PM E-DRIVE subsidy</strong>, where applicable.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Can I purchase more than one scooter with a single booking registration?</summary>
                    <div class="faq-answer">
                        No, each booking registration allows the purchase of only one vehicle per individual.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">My booking was made in a different name. Can I purchase the vehicle under another name?</summary>
                    <div class="faq-answer">
                        Yes. If the booking was made online in one name, the vehicle can be purchased under a different name at the time of delivery. Simply inform your nearest authorised dealership during the purchase process and provide the necessary documents, before invoicing of the vehicle.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Can I change the variant for my existing order? How do I do that?</summary>
                    <div class="faq-answer">
                        Yes, you may change your variant. For online bookings, please send a written request to our
                        Customer Care team and share a copy with your dealership to update your order.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">I've heard that electric scooters don't require a licence to ride them. Why do I need a licence to ride the Kinetic DX and DX+?</summary>
                    <div class="faq-answer">
                        The Kinetic DX and DX+ are classified as <strong>high-speed electric two-wheelers</strong>, with motor power exceeding 250 W and top speeds above 25 km/h. Consequently, they are subject to the same regulatory requirements as petrol-powered motorcycles and scooters in India, and a valid driving licence is mandatory.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Will I receive a PM E-DRIVE subsidy when purchasing the vehicle?</summary>
                    <div class="faq-answer">
                        Yes, you may be eligible for the <strong>PM E-DRIVE subsidy</strong> when purchasing a Kinetic electric vehicle (EV), subject to government guidelines and eligibility criteria.
                    </div>
                </details>
            </div>

            <div class="faq-group">
                <h2>Vehicle Warranty</h2>
                <details class="faq-item">
                    <summary class="faq-question">What is the warranty policy?</summary>
                    <div class="faq-answer">
                        The warranty covers the vehicle against manufacturing defects for a specified duration or mileage, in line with the terms and conditions set by the manufacturer.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What is the duration of the standard warranty on my Kinetic EV?</summary>
                    <div class="faq-answer">
                        The standard warranty for a Kinetic EV is 3 years or 50,000 km, whichever comes first.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What is covered under the vehicle warranty?</summary>
                    <div class="faq-answer">
                        The warranty covers key components of your Kinetic EV, including the battery, motor, controller, charger, cluster assembly, and chassis.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What is not covered under the vehicle warranty?</summary>
                    <div class="faq-answer">
                        The warranty does not cover normal wear-and-tear items (such as tyres, brake pads, and bulbs) or any physical damage caused by accidents, misuse, or unauthorised modifications.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">How can I extend my vehicle warranty?</summary>
                    <div class="faq-answer">
                        You can extend your vehicle warranty by opting for an Extended Warranty package through your nearest authorised Kinetic Watts and Volts dealership.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Can my vehicle's warranty become invalid?</summary>
                    <div class="faq-answer">
                        Yes, the vehicle's warranty may become void if <strong>Periodic Maintenance Service (PMS)</strong> is not carried out as per the prescribed schedule at an authorised service centre.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">How much warranty do I get on the battery?</summary>
                    <div class="faq-answer">
                        <p>The battery is covered under the following warranty options:</p>
                        <ul>
                            <li>
                                <strong>Standard Warranty:</strong> 3 years or 50,000 km
                            </li>
                            <li>
                                <strong>Extended Warranty (Option 1):</strong> An additional 2 years or 20,000 km
                            </li>
                            <li>
                                <strong>Extended Warranty (Option 2):</strong> Up to 6 years or 50,000 km in total
                            </li>
                        </ul>
                    </div>
                </details>
            </div>
        </div>
    </section>
</div>
<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php';
endLayout(); ?>