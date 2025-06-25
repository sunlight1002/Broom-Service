import React from 'react';
import { useNavigate } from 'react-router-dom';

const RenovationPage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>Post-Renovation Cleaning</h1>
            
            <div className="service-description">
                <p>
                    Have you just finished renovating? Moving to a new property? Broom Service offers the
                    perfect solution that will save you a lot of headache and frustration involved in cleaning
                    and organizing your house or apartment - and free you up for a coffee with the neighbors.
                </p>
                <p>
                    Thorough cleaning before moving in or after renovation includes comprehensive services
                    to ensure your space is spotless and ready for use.
                </p>
            </div>

            <div className="service-features">
                <h3>Post-Renovation Services Include</h3>
                <ul>
                    <li>Deep cleaning and disinfection in every corner - even in hard-to-reach places</li>
                    <li>Window cleaning, shutters, and tracks at any height</li>
                    <li>Quality control and supervision: Every job is performed under the supervision of a professional work manager</li>
                    <li>Professional and skilled workers who ensure a clean and inviting environment</li>
                    <li>The highest quality materials and equipment, suitable for various surfaces</li>
                    <li>Removal of dirt and tough stains, including renovation stains</li>
                    <li>Payment upon completion of the work</li>
                </ul>
            </div>

            <div className="renovation-packages">
                <h2>Renovation Cleaning Packages</h2>
                
                <div className="package-grid">
                    <div className="package-card">
                        <h3>Basic Post-Renovation</h3>
                        <p>Essential cleaning after renovation work</p>
                        <ul>
                            <li>Dust removal from all surfaces</li>
                            <li>Basic floor cleaning</li>
                            <li>Window cleaning (interior)</li>
                            <li>Trash and debris removal</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Standard Post-Renovation</h3>
                        <p>Comprehensive cleaning for most renovation projects</p>
                        <ul>
                            <li>All basic services included</li>
                            <li>Deep cleaning of all surfaces</li>
                            <li>Interior and exterior window cleaning</li>
                            <li>Kitchen and bathroom sanitization</li>
                            <li>Air duct cleaning</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Premium Post-Renovation</h3>
                        <p>Complete transformation cleaning for luxury renovations</p>
                        <ul>
                            <li>All standard services included</li>
                            <li>Complete home transformation</li>
                            <li>Premium surface treatments</li>
                            <li>Professional organization services</li>
                            <li>Final quality inspection</li>
                            <li>Move-in ready preparation</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="cleaning-process">
                <h2>Our Post-Renovation Process</h2>
                <div className="process-steps">
                    <div className="step">
                        <h3>1. Assessment</h3>
                        <p>We evaluate the scope of renovation work and cleaning requirements</p>
                    </div>
                    <div className="step">
                        <h3>2. Debris Removal</h3>
                        <p>Remove all construction debris and materials</p>
                    </div>
                    <div className="step">
                        <h3>3. Deep Cleaning</h3>
                        <p>Comprehensive cleaning of all surfaces and areas</p>
                    </div>
                    <div className="step">
                        <h3>4. Sanitization</h3>
                        <p>Disinfect and sanitize all areas</p>
                    </div>
                    <div className="step">
                        <h3>5. Final Inspection</h3>
                        <p>Quality control to ensure move-in readiness</p>
                    </div>
                </div>
            </div>

            <div className="why-choose">
                <h2>Why Choose Broom Service for Post-Renovation Cleaning?</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>🏗️ Experience</h3>
                        <p>Since 2015 with recommendations from the best contractors in Israel</p>
                    </div>
                    <div className="benefit-item">
                        <h3>👥 Professional Team</h3>
                        <p>Skilled workers supervised by professional work managers</p>
                    </div>
                    <div className="benefit-item">
                        <h3>🛡️ Quality Guarantee</h3>
                        <p>Work completed to your satisfaction before payment</p>
                    </div>
                    <div className="benefit-item">
                        <h3>💎 Premium Equipment</h3>
                        <p>Highest quality materials and equipment for all surfaces</p>
                    </div>
                </div>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your post-renovation cleaning needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default RenovationPage; 