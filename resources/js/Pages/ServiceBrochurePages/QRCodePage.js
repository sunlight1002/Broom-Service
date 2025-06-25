import React from 'react';

const qrCodes = [
  {
    img: '/images/senior.jpg',
    label: 'Our ratings on Facebook',
  },
  {
    img: '/images/senior.jpg',
    label: 'Our ratings on Google',
  },
  {
    img: '/images/senior.jpg',
    label: 'Quick contact',
  },
  {
    img: '/images/senior.jpg',
    label: 'Connect with our bot',
  },
  {
    img: '/images/senior.jpg',
    label: 'Contact with the work manager',
  },
  {
    img: '/images/senior.jpg',
    label: 'Customer portal',
  },
];

const QRCodePage = () => {
  return (
    <div className="qr-page">
      <div className="container">
        <h1 className="qr-title">Thank you for choosing Broom Service</h1>
        <p className="qr-intro">
          At Broom Service, we are grateful for your choice of our services. Our work is our best business card, and you will be able to see the difference in the final result. Our commitment to quality and customer satisfaction is at the top of our priorities. Below are QR code links for more information and to get in touch:
        </p>
        <div className="qr-grid">
          {qrCodes.map((qr, idx) => (
            <div className="qr-card" key={idx}>
              <img src={qr.img} alt={qr.label} className="qr-img" />
              <div className="qr-label">{qr.label}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default QRCodePage; 