import React from 'react';
import { useNavigate } from 'react-router-dom';

const WindowCleaningPage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>Professional Window Cleaning</h1>
            
            <div className="service-description">
                <p>
                    We specialize in window cleaning, providing thorough and professional cleaning of all
                    home windows as per request. We offer reliable and professional service to our customers.
                    Additionally, we provide rope access services performed by a skilled team with certifications
                    and professional training, executing the work with the utmost professionalism in rope gliding!
                </p>
                <p>
                    If you were looking for professional window cleaning services, either on a regular or one-time basis, 
                    you've probably come to Broom Service. Our company offers a professional solution, either regularly 
                    or as a one-time service, for cleaning all types of windows at any height.
                </p>
            </div>

            <div className="service-features">
                <h3>Window Cleaning Services</h3>
                <ul>
                    <li>Regular window cleaning maintenance</li>
                    <li>Post-renovation window cleaning</li>
                    <li>Pre-occupancy window preparation</li>
                    <li>Rope access work for high-rise buildings</li>
                    <li>Interior and exterior window cleaning</li>
                    <li>Window frame and track cleaning</li>
                    <li>Screen cleaning and maintenance</li>
                    <li>Specialized equipment for different window types</li>
                </ul>
            </div>

            <div className="service-applications">
                <h2>Service Applications</h2>
                <p>Our window cleaning services are suitable for:</p>
                <div className="applications-grid">
                    <div className="application-item">
                        <h3>🏠 Private Homes</h3>
                        <p>Residential window cleaning for houses and apartments</p>
                    </div>
                    <div className="application-item">
                        <h3>🏢 Business Properties</h3>
                        <p>Commercial building window maintenance</p>
                    </div>
                    <div className="application-item">
                        <h3>🏛️ Public Sectors</h3>
                        <p>Government buildings and public facilities</p>
                    </div>
                    <div className="application-item">
                        <h3>🏨 Hospitality</h3>
                        <p>Hotels, restaurants, and hospitality venues</p>
                    </div>
                </div>
            </div>

            <div className="rope-access">
                <h2>Rope Access Services</h2>
                <p>
                    For windows at any height, our certified rope access team provides safe and professional 
                    cleaning services. Our team members are trained and certified in rope access techniques, 
                    ensuring the highest safety standards while delivering exceptional results.
                </p>
                
                <div className="rope-features">
                    <h3>Rope Access Benefits</h3>
                    <ul>
                        <li>Safe access to windows at any height</li>
                        <li>Certified and trained professionals</li>
                        <li>Minimal disruption to building operations</li>
                        <li>No need for expensive scaffolding</li>
                        <li>Efficient and cost-effective solution</li>
                        <li>Comprehensive safety protocols</li>
                    </ul>
                </div>
            </div>

            <div className="cleaning-process">
                <h2>Our Cleaning Process</h2>
                <div className="process-steps">
                    <div className="step">
                        <h3>1. Assessment</h3>
                        <p>We assess the condition and accessibility of your windows</p>
                    </div>
                    <div className="step">
                        <h3>2. Preparation</h3>
                        <p>We prepare the area and set up necessary safety measures</p>
                    </div>
                    <div className="step">
                        <h3>3. Cleaning</h3>
                        <p>Professional cleaning using appropriate methods and equipment</p>
                    </div>
                    <div className="step">
                        <h3>4. Inspection</h3>
                        <p>Quality inspection to ensure all windows meet our standards</p>
                    </div>
                </div>
            </div>

            <div className="equipment">
                <h2>Professional Equipment</h2>
                <p>
                    We use state-of-the-art equipment and eco-friendly cleaning solutions to ensure 
                    the best results while protecting your windows and the environment.
                </p>
                <ul>
                    <li>Professional window cleaning tools</li>
                    <li>Eco-friendly cleaning solutions</li>
                    <li>Safety equipment for high-rise work</li>
                    <li>Specialized equipment for different window types</li>
                    <li>Water-fed pole systems for high windows</li>
                </ul>
            </div>

            <div className="scheduling">
                <h2>Scheduling Options</h2>
                <p>We offer flexible scheduling to meet your needs:</p>
                <ul>
                    <li>Regular maintenance schedules</li>
                    <li>One-time cleaning services</li>
                    <li>Emergency cleaning services</li>
                    <li>Seasonal cleaning programs</li>
                    <li>Custom scheduling based on your requirements</li>
                </ul>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your window cleaning needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default WindowCleaningPage; 