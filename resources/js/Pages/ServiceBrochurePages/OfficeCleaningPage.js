import React from 'react';
import { useNavigate } from 'react-router-dom';

const OfficeCleaningPage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>Premium Office Cleaning</h1>
            
            <div className="service-description">
                <p>
                    Do you arrive at the office every morning feeling like something is missing? Perhaps it's
                    the cleanliness and order that don't meet your standards. We completely understand this.
                    An unclean and disorganized office can negatively impact employee productivity and the
                    success of your business.
                </p>
                <p>
                    It's not just about cleaning, but about the quality of life in the office. A clean and 
                    orderly office will significantly improve employee productivity and give you peace of mind.
                </p>
            </div>

            <div className="service-features">
                <h3>Office Cleaning Services</h3>
                <ul>
                    <li>Daily office cleaning and maintenance</li>
                    <li>Deep cleaning of all office areas</li>
                    <li>Kitchen and break room sanitization</li>
                    <li>Bathroom cleaning and disinfection</li>
                    <li>Conference room preparation</li>
                    <li>Reception area maintenance</li>
                    <li>Carpet and upholstery cleaning</li>
                    <li>Window and glass surface cleaning</li>
                </ul>
            </div>

            <div className="office-packages">
                <h2>Office Cleaning Packages</h2>
                
                <div className="package-grid">
                    <div className="package-card">
                        <h3>Basic Office Cleaning</h3>
                        <p>Essential daily cleaning for small offices</p>
                        <ul>
                            <li>Daily trash removal</li>
                            <li>Surface dusting and cleaning</li>
                            <li>Floor vacuuming and mopping</li>
                            <li>Bathroom maintenance</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Standard Office Cleaning</h3>
                        <p>Comprehensive cleaning for medium-sized offices</p>
                        <ul>
                            <li>All basic services included</li>
                            <li>Kitchen and break room cleaning</li>
                            <li>Conference room preparation</li>
                            <li>Window cleaning (interior)</li>
                            <li>Reception area maintenance</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Premium Office Cleaning</h3>
                        <p>Complete office transformation for large companies</p>
                        <ul>
                            <li>All standard services included</li>
                            <li>Complete office deep cleaning</li>
                            <li>Carpet and upholstery cleaning</li>
                            <li>Interior and exterior window cleaning</li>
                            <li>Specialized equipment cleaning</li>
                            <li>Quality control inspection</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="benefits">
                <h2>Benefits of Professional Office Cleaning</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>📈 Increased Productivity</h3>
                        <p>Clean work environments boost employee morale and productivity</p>
                    </div>
                    <div className="benefit-item">
                        <h3>🏥 Better Health</h3>
                        <p>Reduced allergens and bacteria create healthier workplaces</p>
                    </div>
                    <div className="benefit-item">
                        <h3>💼 Professional Image</h3>
                        <p>Maintain a professional appearance for clients and visitors</p>
                    </div>
                    <div className="benefit-item">
                        <h3>💰 Cost Savings</h3>
                        <p>Prevent expensive repairs and equipment replacement</p>
                    </div>
                </div>
            </div>

            <div className="why-choose">
                <h2>Why Choose Broom Service for Office Cleaning?</h2>
                <p>
                    We are committed to quality and excellent service, so you can focus on what really matters - your business.
                    Our permanent, skilled, and experienced workers, who have undergone meticulous screening and training, 
                    will take care of every corner of your office because your success and that of your team is our success.
                </p>
                
                <div className="company-commitment">
                    <h3>Our Commitment to Your Business</h3>
                    <ul>
                        <li>Flexible scheduling to minimize business disruption</li>
                        <li>Professional, reliable, and trustworthy staff</li>
                        <li>Customized cleaning plans for your specific needs</li>
                        <li>Quality control and supervision</li>
                        <li>Eco-friendly cleaning solutions</li>
                        <li>Competitive pricing and transparent billing</li>
                    </ul>
                </div>
            </div>

            <div className="scheduling">
                <h2>Flexible Scheduling Options</h2>
                <p>We offer scheduling options that work around your business hours:</p>
                <ul>
                    <li>Early morning cleaning (before business hours)</li>
                    <li>Evening cleaning (after business hours)</li>
                    <li>Weekend cleaning services</li>
                    <li>Custom scheduling based on your needs</li>
                    <li>Emergency cleaning services</li>
                </ul>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your office cleaning needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default OfficeCleaningPage; 