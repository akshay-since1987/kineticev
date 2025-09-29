<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php';
require_once 'components/layout.php';

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
<div class="about-page">
    <section class="intro">
        <!-- <a href="#" onclick="window.history.back();" class="back-arrow">
            <i class="fa fa-arrow-left"></i>
        </a> -->
        <div class="container title-section-wrapper">
            <div class="page-title-section">
                <div class="intro-heading">
                    <h2>About</h2>
                </div>
            </div>
        </div>
    </section>

    <section class="about-text">
        <div class="about-text container">

            <p>Kinetic Watts and Volts Ltd. is the electric mobility and energy solutions subsidiary of Kinetic
                Engineering
                Ltd.
                proudly carrying forward Kinetic India's 50-year legacy of trust, innovation, and engineering excellence
                into the EV era.</p>

            <p> To power the next wave of electric mobility solutions, the State-of-the-art manufacturing plant of
                Kinetic
                Watts and Volts Ltd,
                spread over 87,000 sq. ft., is built for scalable, sustainable production and equipped with cutting-edge
                automation, reflecting our commitment to delivering safe, high-quality, and truly Made-in-India EV
                products,
                powered by AIS-156 certified Range-X LFP batteries. It marks the beginning of an exciting journey to
                redefine electric mobility for India and beyond.</p>

            <p>At Kinetic Watts and Volts Ltd, we build on decades of trust and engineering excellence to electrify the
                future of mobility,
                empowering journeys that are innovative, reliable, and proudly Indian.</p>
        </div>

        <h4>Core Values</h4>
        <div class="core-values">
            <picture>
                <source srcset="/-/images/core-values-desktop.png" media="(min-width: 768px)" />
                <source srcset="/-/images/core-values-mobile.png" media="(max-width: 767px)" />
                <img src="/-/images/india.png" alt="Core Values" class="core-values-image" />
            </picture>
        </div>
    </section>

    <section class="mission-vision container">
        <picture>
            <source srcset="/-/images/mission-vision-desktop.png" media="(min-width: 768px)" />
            <source srcset="/-/images/mission-vision-mobile.png" media="(max-width: 767px)" />
            <img src="/-/images/mission-vision-desktop.png" alt="Mission and Vision" class="mission-vision-image" />
        </picture>
    </section>
    <section class="leadership container">
        <article class="leader">
            <img src="/-/images/dr-arun-firodia.png" alt="Dr. Arun Firodia" class="leadership-image" />
            <div class="content">
                <h5>The Driving Force</h5>
                <h4>Padma Shri Dr. Arun Firodia</h4>
                <p>Dr. Arun Firodia, Chairman, Kinetic India, is a visionary engineer and pioneering entrepreneur who
                    continues to guide Kinetic India with his deep commitment to innovation and nation-building. His
                    forward-thinking approach led to the creation of the iconic Luna, India's first indigenous moped
                    that
                    became a cultural touchstone and transformed everyday mobility.</p>
                <p>Under his leadership, Kinetic brought world-class scooter technology to India, redefining standards
                    of
                    design, performance, and reliability. Today, Dr. Firodia's enduring vision keeps Kinetic India at
                    the
                    forefront of India's dynamic mobility landscape</p>
            </div>
        </article>

        <article class="leader">
            <img src="/-/images/ajinkya-firodia.png" alt="Ajinkya Firodia" class="leadership-image" />
            <div class="content">
                <h4>Ajinkya Firodia</h4>
                <p>Vice Chairman and Managing Director of Kinetic Watts and Volts Ltd., is a dynamic leader with a
                    strong
                    entrepreneurial spirit. </p>

                <p>After graduating from Brown University, he gained valuable experience in investment banking at JP
                    Morgan
                    Chase. Returning to India, he led new initiatives within Kinetic India, combining legacy strengths
                    with
                    future-ready strategies.</p>
                <p>At Kinetic Watts and Volts, his vision is centred on advancing India's electric mobility revolution
                    through innovative EV solutions and cutting-edge technologies. With his exceptional leadership and
                    commitment to sustainable innovation, Ajinkya Firodia is steering the company to empower a cleaner,
                    greener future for mobility</p>
            </div>
        </article>
    </section>
    <section class="directors container">
        <div class="director-card">
            <img src="/-/images/directors/jayashree-arun-firodia.jpg" alt="Jayashree Arun Firodia">
            <h3>Jayashree Arun Firodia</h3>
            <p class="designation">Non-Executive Director</p>
        </div>

        <div class="director-card">
            <img src="/-/images/directors/aarzoo-alamin-lokhandwala.jpg" alt="Arzoo Alamin Lokhandwala">
            <h3>Arzoo Alamin Lokhandwala</h3>
            <p class="designation">Executive Director</p>
        </div>

        <div class="director-card">
            <img src="/-/images/directors/rohit-bafana.jpg" alt="Rohit Bafana">
            <h3>Rohit Bafana</h3>
            <p class="designation">Non-Executive Independent Director</p>
        </div>

        <div class="director-card">
            <img src="/-/images/directors/piyush-munot.jpg" alt="Piyush Munot">
            <h3>Piyush Munot</h3>
            <p class="designation">Non-Executive Independent Director</p>
        </div>
    </section>
    <section class="call-to-action">
        <div class="link red">
            <a target="_blank" href="https://kineticindia.com/">View Corporate Page</a>
        </div>
        <div class="link blue">
            <a target="_blank" href="https://iamkinetic.in">View Legacy Page</a>
        </div>
    </section>
</div>
<?php
// Initialize production timezone guard
require_once __DIR__ . '/production-timezone-guard.php'; endLayout(); ?>