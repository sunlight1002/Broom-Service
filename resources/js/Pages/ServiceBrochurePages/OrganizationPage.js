import React from 'react';
import { useNavigate } from 'react-router-dom';

const OrganizationPage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>Professional Organization Services</h1>
            
            <div className="service-description">
                <p>
                    Creating maximum order and organization in the home, professionally sorting household
                    items to maximize storage space, and re-storing using creative storage solutions
                    that maintain order over time. We specialize in organizing and arranging wardrobes, 
                    closets, corners, storerooms, kitchen cabinets, and more.
                </p>
            </div>

            <div className="service-features">
                <h3>Organization Services</h3>
                <ul>
                    <li>Professional and efficient organization</li>
                    <li>Sorting clothes by seasons</li>
                    <li>Wardrobe and closet organization</li>
                    <li>Kitchen cabinet organization</li>
                    <li>Storeroom and corner organization</li>
                    <li>Creative storage solutions</li>
                    <li>Space maximization techniques</li>
                    <li>Long-term organization maintenance</li>
                </ul>
            </div>

            <div className="moving-services">
                <h2>Moving Services</h2>
                
                <div className="moving-packages">
                    <div className="package-card">
                        <h3>Packing Services</h3>
                        <p>Packing household contents before moving in an organized and well-arranged manner that eases unpacking in the new home. The packing is done meticulously and with utmost sensitivity.</p>
                        <ul>
                            <li>Professional packing of all household items</li>
                            <li>Organized labeling system</li>
                            <li>Fragile item protection</li>
                            <li>Efficient space utilization</li>
                            <li>Inventory management</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Unpacking Services</h3>
                        <p>Unpacking household contents in the new home while ensuring order and organization, tailored to the client's needs and as per their request, in a professional and unique manner that preserves aesthetics and exemplary order.</p>
                        <ul>
                            <li>Systematic unpacking process</li>
                            <li>Immediate organization setup</li>
                            <li>Custom organization solutions</li>
                            <li>Aesthetic arrangement</li>
                            <li>Post-move organization</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="organization-areas">
                <h2>Areas We Organize</h2>
                <div className="areas-grid">
                    <div className="area-item">
                        <h3>👕 Wardrobes & Closets</h3>
                        <p>Professional clothing organization and storage optimization</p>
                    </div>
                    <div className="area-item">
                        <h3>🍳 Kitchen Cabinets</h3>
                        <p>Efficient kitchen organization and storage solutions</p>
                    </div>
                    <div className="area-item">
                        <h3>📚 Home Offices</h3>
                        <p>Workspace organization for productivity and efficiency</p>
                    </div>
                    <div className="area-item">
                        <h3>🧸 Children's Rooms</h3>
                        <p>Kid-friendly organization systems and storage</p>
                    </div>
                </div>
            </div>

            <div className="benefits">
                <h2>Benefits of Professional Organization</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>🏠 More Space</h3>
                        <p>Maximize your available space with efficient organization</p>
                    </div>
                    <div className="benefit-item">
                        <h3>⏰ Time Savings</h3>
                        <p>Find what you need quickly with organized systems</p>
                    </div>
                    <div className="benefit-item">
                        <h3>😌 Reduced Stress</h3>
                        <p>Enjoy a clutter-free and organized living environment</p>
                    </div>
                    <div className="benefit-item">
                        <h3>💎 Increased Value</h3>
                        <p>Well-organized spaces enhance property value</p>
                    </div>
                </div>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your organization needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default OrganizationPage; 