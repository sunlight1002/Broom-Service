import React, { useState } from 'react';

const ServicesPage = () => {
    const [activeService, setActiveService] = useState('home-cleaning');

    const services = [
        {
            id: 'home-cleaning',
            title: 'Home Cleaning Services',
            subtitle: 'Regular and deep cleaning for your home',
            image: '/images/senior.jpg',
            description: 'Comprehensive home maintenance with flexible packages to suit your needs. We offer 2*, 3*, 4*, and 5* packages with increasing levels of service.',
            features: [
                'Comprehensive dusting and surface cleaning',
                'Kitchen deep cleaning and sanitization',
                'Bathroom thorough cleaning and disinfection',
                'Floor cleaning and maintenance',
                'Window and glass surface cleaning',
                'Vacuuming and carpet care',
                'Organization and tidying up'
            ],
            packages: [
                { name: '2-Star Package', description: 'Basic cleaning service for regular maintenance' },
                { name: '3-Star Package', description: 'Enhanced cleaning with additional attention to detail' },
                { name: '4-Star Package', description: 'Premium cleaning service for luxury homes' },
                { name: '5-Star Package', description: 'Ultimate luxury cleaning experience' }
            ]
        },
        {
            id: 'office-cleaning',
            title: 'Office Cleaning',
            subtitle: 'Professional workplace cleaning for enhanced productivity',
            image: '/images/senior.jpg',
            description: 'Keep your workplace clean and professional with our comprehensive office cleaning services. We understand the importance of maintaining a clean and healthy work environment.',
            features: [
                'Daily office cleaning and maintenance',
                'Deep cleaning of all office areas',
                'Kitchen and break room sanitization',
                'Bathroom cleaning and disinfection',
                'Conference room preparation',
                'Reception area maintenance',
                'Carpet and upholstery cleaning'
            ],
            packages: [
                { name: 'Basic Office Cleaning', description: 'Essential daily cleaning for small offices' },
                { name: 'Standard Office Cleaning', description: 'Comprehensive cleaning for medium-sized offices' },
                { name: 'Premium Office Cleaning', description: 'Complete office transformation for large companies' }
            ]
        },
        {
            id: 'window-cleaning',
            title: 'Window Cleaning',
            subtitle: 'Professional window cleaning at any height with rope access',
            image: '/images/senior.jpg',
            description: 'We specialize in window cleaning, providing thorough and professional cleaning of all home windows. We offer rope access services for high-rise buildings.',
            features: [
                'Regular window cleaning maintenance',
                'Post-renovation window cleaning',
                'Pre-occupancy window preparation',
                'Rope access work for high-rise buildings',
                'Interior and exterior window cleaning',
                'Window frame and track cleaning',
                'Screen cleaning and maintenance'
            ],
            packages: [
                { name: 'Residential Windows', description: 'Home window cleaning services' },
                { name: 'Commercial Windows', description: 'Office and business window cleaning' },
                { name: 'High-Rise Windows', description: 'Rope access window cleaning' }
            ]
        },
        {
            id: 'renovation-cleaning',
            title: 'Post-Renovation Cleaning',
            subtitle: 'Thorough cleaning after construction or renovation work',
            image: '/images/senior.jpg',
            description: 'Perfect solution for cleaning after renovation or moving to a new property. We save you headache and frustration involved in cleaning and organizing.',
            features: [
                'Deep cleaning and disinfection in every corner',
                'Window cleaning, shutters, and tracks at any height',
                'Quality control and supervision by work manager',
                'Professional and skilled workers',
                'Highest quality materials and equipment',
                'Removal of dirt and tough renovation stains',
                'Payment upon completion of the work'
            ],
            packages: [
                { name: 'Basic Post-Renovation', description: 'Essential cleaning after renovation work' },
                { name: 'Standard Post-Renovation', description: 'Comprehensive cleaning for most renovation projects' },
                { name: 'Premium Post-Renovation', description: 'Complete transformation cleaning for luxury renovations' }
            ]
        },
        {
            id: 'grill-cleaning',
            title: 'Grill & Oven Cleaning',
            subtitle: 'Professional cleaning for gas grills, outdoor kitchens, and ovens',
            image: '/images/senior.jpg',
            description: 'Specialized cleaning of gas grills, outdoor kitchens, and ovens. We ensure the longevity of your equipment and enhance your cooking experience.',
            features: [
                'Removal of tough grease and buildup',
                'Thorough cleaning of grates and radiants',
                'Use of natural cleaning agents',
                'Deep cleaning of all grill components',
                'Oven interior and exterior cleaning',
                'Outdoor kitchen surface cleaning',
                'Safety inspection and maintenance check'
            ],
            packages: [
                { name: 'Gas Grill Cleaning', description: 'Professional cleaning of all gas grill types' },
                { name: 'Oven Cleaning', description: 'Deep cleaning of conventional and convection ovens' },
                { name: 'Outdoor Kitchen', description: 'Complete outdoor kitchen cleaning and maintenance' }
            ]
        },
        {
            id: 'organization',
            title: 'Organization Services',
            subtitle: 'Professional wardrobe organization and packing services',
            image: '/images/senior.jpg',
            description: 'Creating maximum order and organization in the home, professionally sorting household items to maximize storage space.',
            features: [
                'Professional and efficient organization',
                'Sorting clothes by seasons',
                'Wardrobe and closet organization',
                'Kitchen cabinet organization',
                'Creative storage solutions',
                'Space maximization techniques',
                'Packing and unpacking services'
            ],
            packages: [
                { name: 'Wardrobe Organization', description: 'Professional clothing organization and storage' },
                { name: 'Home Organization', description: 'Complete home organization services' },
                { name: 'Moving Services', description: 'Packing and unpacking for moves' }
            ]
        },
        {
            id: 'short-term-rental',
            title: 'Short-Term Rental Service (Airbnb)',
            subtitle: 'Cleaning & maintenance for short-term rental properties',
            image: '/images/senior.jpg',
            description: `Do you rent out an apartment for short periods? We have a solution that will help you manage your property with peace of mind.\n\nWe offer cleaning and maintenance of apartments for short periods as per request, at the highest level by permanent and experienced employees who are legally insured and under the supervision of a work manager.\n\nAdherence to schedules (check-out - check-in) without delays or postponements, the most advanced cleaning materials and equipment at our expense, window cleaning at any height (including rappelling), and on-site work managers who will check the work for you and in your place to your satisfaction.\n\nBroom Service takes care of your property! You can also offer the service to tenants staying in your apartment as per request, all on a single consolidated invoice at the end of the month.\n\nJoin the success! All our clients are Super Hosts! Let us upgrade your property to a 5-star boutique hotel level!\n\nCleaning and maintenance of the property by permanent and experienced chambermaids who are legally insured and under the supervision of a work manager. Adherence to schedules (check-out - check-in) without delays or postponements. The most advanced cleaning materials and equipment - at our expense.`,
            features: [
                'Cleaning and maintenance for short-term rental apartments',
                'Permanent, experienced, and insured staff',
                'Supervised by a work manager',
                'Strict adherence to check-in/check-out schedules',
                'Advanced cleaning materials and equipment included',
                'Window cleaning at any height (including rappelling)',
                'On-site work manager for quality control',
                'Single consolidated invoice for all services',
                'Upgrade your property to 5-star boutique hotel level',
                'All clients are Super Hosts!'
            ],
            packages: []
        },
        {
            id: 'sofa-carpet-curtain',
            title: 'Professional Cleaning for Sofas, Carpets, and Curtains',
            subtitle: 'Deep cleaning for all types of fabrics and materials',
            image: '/images/senior.jpg',
            description: `We perform cleaning services at the client's home or through pick-up and return from the client's home, as per your choice. Our service includes professional responsibility for the work, to ensure your satisfaction and the quality of the outcome. Payment for the service is made upon completion of the work, to ensure complete peace of mind for our customers.\n\nWe work with the most advanced materials and equipment in the world, to ensure thorough, deep, and safe cleaning for all types of fabrics and materials. Additionally, we offer attractive prices and special offers for our company's clients, to ensure that you receive the best service at the best price.\n\nChoose Broom Service for cleaning your sofas, carpets, and curtains, and enjoy professional, reliable, and high-quality service.`,
            features: [
                'Cleaning at your home or pick-up and return service',
                'Professional responsibility for quality and satisfaction',
                'Payment only upon completion',
                'Advanced materials and equipment',
                'Safe cleaning for all types of fabrics and materials',
                'Attractive prices and special offers for clients',
                'Professional, reliable, and high-quality service'
            ],
            packages: []
        }
    ];

    const currentService = services.find(service => service.id === activeService);

    return (
        <div className="services-page">
            <div className="services-hero">
                <div className="container">
                    <h1>Our Services</h1>
                    <p>Comprehensive cleaning solutions tailored to your needs</p>
                </div>
            </div>

            <div className="services-content">
                <div className="container">
                    <div className="services-layout">
                        <div className="services-sidebar">
                            <h3>All Services</h3>
                            <ul className="service-nav">
                                {services.map(service => (
                                    <li key={service.id}>
                                        <button 
                                            className={activeService === service.id ? 'active' : ''}
                                            onClick={() => setActiveService(service.id)}
                                        >
                                            {service.title}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="service-details">
                            {currentService && (
                                <div className="service-detail">
                                    <div className="service-header">
                                        <div className="service-image">
                                            <img src={currentService.image} alt={currentService.title} />
                                        </div>
                                        <div className="service-info">
                                            <h2>{currentService.title}</h2>
                                            <p className="service-subtitle">{currentService.subtitle}</p>
                                            <p className="service-description">{currentService.description}</p>
                                        </div>
                                    </div>

                                    <div className="service-features">
                                        <h3>What's Included</h3>
                                        <ul>
                                            {currentService.features.map((feature, index) => (
                                                <li key={index}>{feature}</li>
                                            ))}
                                        </ul>
                                    </div>

                                    <div className="service-packages">
                                        <h3>Service Packages</h3>
                                        <div className="packages-grid">
                                            {currentService.packages.map((pkg, index) => (
                                                <div key={index} className="package-card">
                                                    <h4>{pkg.name}</h4>
                                                    <p>{pkg.description}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Cleaning Tips Section */}
            <div className="container cleaning-tips-section">
                <h2>Cleaning Tips & Recommended Products</h2>
                <div className="cleaning-tips-grid">
                    <div className="cleaning-tip-card">
                        <h3>Detergents and Equipment</h3>
                        <p>In the world of cleaning agents, they can generally be divided into two main types: acidic and basic (alkaline) detergents. It's crucial to know which type to use on each surface to prevent chemical damage. If a surface could react with an acidic detergent, only a basic detergent should be used.</p>
                        <ul>
                            <li>Wood - may react with acidic detergents.</li>
                            <li>Metal - also may react.</li>
                            <li>Plastic - will not react.</li>
                            <li>Glass - will not react.</li>
                            <li>Any type of stone or Caesarstone flooring - will react.</li>
                            <li>Any type of ceramic, porcelain flooring - will probably not react.</li>
                        </ul>
                        <p>For equipment, the best way to clean surfaces is with a microfiber cloth, which gathers both dust and tough stains and helps disinfect surfaces. For cleaning floors, we also use a microfiber cloth. Before washing or wiping a floor, especially if it's wooden, it's recommended to vacuum it first. Therefore, a vacuum cleaner is recommended, particularly for cleaning carpets, sofas, and other fabrics. Gloves will help protect from any strong detergents. We prefer to use a non-scratching Scotch-Brite for cleaning tough stains, rather than a hard or white sponge that might leave marks on the surface being cleaned.</p>
                        <ul>
                            <li>Floor mop</li>
                            <li>Vacuum cleaner</li>
                            <li>Two small microfiber cloths</li>
                            <li>One large cloth for the floor</li>
                            <li>A bucket</li>
                            <li>Gloves</li>
                            <li>2 non-scratch Japanese sponges (Scotch-Brite)</li>
                        </ul>
                    </div>
                    <div className="cleaning-tip-card">
                        <h3>Recommended Cleaning Products</h3>
                        <p>For everyday cleaning of regular surfaces, we recommend a basic cleaning agent such as soap or any non-acidic general surface cleaner. Our preferred choice is "Fairy," which is highly concentrated and leaves surfaces clean and shiny. However, if you prefer an alternative, any non-acidic general surface cleaner will do the job.</p>
                        <p>To tackle tough stains, limescale buildup, or for disinfecting purposes, we recommend "Anti-Calc" by Sano or "Ruby" by Suter. These products are effective in cleaning and disinfecting surfaces, removing limescale, and eliminating stubborn stains, ensuring a perfect result.</p>
                        <p>For shiny surfaces or windows, a simple window cleaner will work. However, we prefer a mixture of vinegar and soap, which not only leaves surfaces shinier but also helps remove any limescale from the windows if present.</p>
                        <p>For floor cleaning, we recommend a non-acidic floor cleaner. Our top choice, based on our experience, is again Fairy, due to its chemical composition that ensures maximum cleanliness and shine without damaging the floor. In the case of real wood floors, we advise not to use any cleaning agent at all, but rather a semi-wet, wrung-out microfiber cloth.</p>
                        <p>All the above-mentioned cleaning products are part of our recommended cleaning system, designed to handle a variety of cleaning challenges while ensuring the longevity and appearance of the surfaces.</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ServicesPage; 