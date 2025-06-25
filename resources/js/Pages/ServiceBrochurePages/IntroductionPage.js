import React from 'react';

const IntroductionPage = () => {
    return (
        <div className="page fade-in">
            <h1>Introduction</h1>
            
            <div className="company-intro">
                <h2>About Broom Service</h2>
                <p>
                    Broom Service Ltd. was established in 2015 and operates with the approval and license of
                    the Ministry of Industry, Trade and Labor. <strong>License Number 4569.</strong>
                </p>
                <p>
                    Our company is dedicated to ensuring the peace of mind of our clients by providing top-tier service. 
                    To achieve this, we recruit only the most skilled and professional workers, who undergo thorough 
                    training and professional orientation before joining our team. Our employees are permanent staff 
                    members, receiving competitive wages and excellent working conditions in compliance with the law.
                </p>
                <p>
                    All our company's operations are supervised by our in-house work manager, ensuring that
                    the work is completed to your satisfaction and in accordance with our high standards.
                    Payment is always made at the end of the job or monthly, only after we have confirmed with
                    you that the work has been completed to your satisfaction.
                </p>
            </div>

            <div className="services-overview">
                <h2>Our Services</h2>
                <p>
                    Broom Service offers regular and one-time cleaning services, specially tailored to meet
                    the needs and requirements of each client. We understand the importance of a clean and
                    orderly living environment, and therefore we offer high-quality services that will enhance
                    your living experience.
                </p>
            </div>

            <div className="premium-service">
                <h2>Premium Level House Cleaning and Organizing</h2>
                <p>
                    House cleaning and organizing? A cleaner? A maid? Social benefits? Faucets? Delays?
                    Reliability? It's time to free yourself for the truly important things. We'll take care of everything else!
                </p>
                <p>
                    If you live in a private house or a luxury apartment, you surely know how hard it is to find a
                    quality cleaning service you can rely on. But now you've found Broom Service.
                </p>
                <p>
                    We are a luxury cleaning company, approved by the Ministry of Economy, with experience
                    since 2015 in transforming homes and luxury apartments into pampering places at the level
                    of a five-star hotel.
                </p>
                
                <div className="service-features">
                    <h3>Why Choose Broom Service?</h3>
                    <ul>
                        <li>We only send permanent, skilled, and talented workers who have undergone thorough training and selection</li>
                        <li>Provide the highest level of service and work, with great care and meticulousness</li>
                        <li>All work is performed under the supervision of a work manager</li>
                        <li>Proper and professional treatment of various surfaces</li>
                        <li>Window cleaning at any height</li>
                        <li>Floor renewal and the most professional level of cupboard organizing</li>
                        <li>Peace of mind knowing you're working directly with the company</li>
                        <li>Neat invoice at the end of the month</li>
                        <li>No additional payments or dealing with workers' employment conditions</li>
                    </ul>
                </div>
                
                <p>
                    We take care of all this and promise to provide first-class service at a five-star level. 
                    A clean and orderly home at a five-star level - without any hassle or effort!
                </p>
            </div>

            <div className="company-values">
                <h2>Our Commitment</h2>
                <div className="values-grid">
                    <div className="value-item">
                        <h3>🏆 Quality</h3>
                        <p>We maintain the highest standards in all our services</p>
                    </div>
                    <div className="value-item">
                        <h3>👥 Professionalism</h3>
                        <p>Our team is trained, experienced, and dedicated to excellence</p>
                    </div>
                    <div className="value-item">
                        <h3>💯 Reliability</h3>
                        <p>We deliver on our promises, every time</p>
                    </div>
                    <div className="value-item">
                        <h3>🛡️ Trust</h3>
                        <p>Licensed, insured, and committed to your satisfaction</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default IntroductionPage; 