import React from 'react';

const AboutPage = () => {
    return (
        <div className="about-page">
            <div className="about-hero">
                <div className="container">
                    <h1>About Broom Service</h1>
                    <p>Professional cleaning services since 2015</p>
                </div>
            </div>

            <div className="about-content">
                <div className="container">
                    <div className="about-section">
                        <div className="about-text">
                            <h2>Our Story</h2>
                            <p>
                                Broom Service Ltd. was established in 2015 and operates with the approval and license of
                                the Ministry of Industry, Trade and Labor. <strong>License Number 4569.</strong>
                            </p>
                            <p>
                                Our company is dedicated to ensuring the peace of mind of our clients by providing top-tier service. 
                                To achieve this, we recruit only the most skilled and professional workers, who undergo thorough 
                                training and professional orientation before joining our team.
                            </p>
                            <p>
                                All our company's operations are supervised by our in-house work manager, ensuring that
                                the work is completed to your satisfaction and in accordance with our high standards.
                                Payment is always made at the end of the job or monthly, only after we have confirmed with
                                you that the work has been completed to your satisfaction.
                            </p>
                        </div>
                        <div className="about-image">
                            <img src="/images/senior.jpg" alt="Broom Service Team" />
                        </div>
                    </div>

                    <div className="mission-section">
                        <div className="mission-content">
                            <h2>Our Mission</h2>
                            <p>
                                We are a luxury cleaning company, approved by the Ministry of Economy, with experience
                                since 2015 in transforming homes and luxury apartments into pampering places at the level
                                of a five-star hotel.
                            </p>
                            <div className="mission-points">
                                <div className="mission-point">
                                    <span className="point-icon">🏆</span>
                                    <h4>Quality Excellence</h4>
                                    <p>We maintain the highest standards in all our services</p>
                                </div>
                                <div className="mission-point">
                                    <span className="point-icon">👥</span>
                                    <h4>Professional Team</h4>
                                    <p>Our team is trained, experienced, and dedicated to excellence</p>
                                </div>
                                <div className="mission-point">
                                    <span className="point-icon">💯</span>
                                    <h4>Customer Satisfaction</h4>
                                    <p>We deliver on our promises, every time</p>
                                </div>
                                <div className="mission-point">
                                    <span className="point-icon">🛡️</span>
                                    <h4>Trust & Reliability</h4>
                                    <p>Licensed, insured, and committed to your satisfaction</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="values-section">
                        <h2>Why Choose Broom Service?</h2>
                        <div className="values-grid">
                            <div className="value-item">
                                <div className="value-icon">👨‍💼</div>
                                <h3>Permanent Staff</h3>
                                <p>We only send permanent, skilled, and talented workers who have undergone thorough training and selection</p>
                            </div>
                            <div className="value-item">
                                <div className="value-icon">🔍</div>
                                <h3>Supervised Work</h3>
                                <p>All work is performed under the supervision of a professional work manager</p>
                            </div>
                            <div className="value-item">
                                <div className="value-icon">✨</div>
                                <h3>Premium Quality</h3>
                                <p>Provide the highest level of service and work, with great care and meticulousness</p>
                            </div>
                            <div className="value-item">
                                <div className="value-icon">🏢</div>
                                <h3>Direct Company</h3>
                                <p>Peace of mind knowing you're working directly with the company</p>
                            </div>
                            <div className="value-item">
                                <div className="value-icon">📄</div>
                                <h3>Neat Invoicing</h3>
                                <p>Neat invoice at the end of the month, no additional payments</p>
                            </div>
                            <div className="value-item">
                                <div className="value-icon">⭐</div>
                                <h3>5-Star Level</h3>
                                <p>First-class service at a five-star level - without any hassle or effort</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="stats-section mb-5">
                <div className="stats-grid">
                    <div className="stat-item">
                        <h3>9+</h3>
                        <p>Years of Experience</p>
                    </div>
                    <div className="stat-item">
                        <h3>1000+</h3>
                        <p>Happy Clients</p>
                    </div>
                    <div className="stat-item">
                        <h3>50+</h3>
                        <p>Professional Staff</p>
                    </div>
                    <div className="stat-item">
                        <h3>100%</h3>
                        <p>Satisfaction Rate</p>
                    </div>
                </div>
            </div>

            <div className="team-section">
                <h2>Our Professional Team</h2>
                <div className="team-grid">
                    <div className="team-member">
                        <div className="member-image">
                            <img src="/images/senior.jpg" alt="Team Member" />
                        </div>
                        <h4>Cleaning Specialists</h4>
                        <p>Skilled professionals trained in all aspects of cleaning</p>
                    </div>
                    <div className="team-member">
                        <div className="member-image">
                            <img src="/images/senior.jpg" alt="Team Member" />
                        </div>
                        <h4>Work Managers</h4>
                        <p>Experienced supervisors ensuring quality control</p>
                    </div>
                    <div className="team-member">
                        <div className="member-image">
                            <img src="/images/senior.jpg" alt="Team Member" />
                        </div>
                        <h4>Specialized Technicians</h4>
                        <p>Experts in window cleaning, organization, and special services</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AboutPage; 