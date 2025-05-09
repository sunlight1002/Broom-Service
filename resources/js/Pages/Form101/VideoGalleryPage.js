import React, { useState, useRef } from 'react';
import "./Video.css"
import CustomVideoPlayer from './CustomVideoPlayer';
import { GrFormPreviousLink } from 'react-icons/gr';
import { useTranslation } from 'react-i18next';

const videos = [
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 1 - V5 כתוביות.mp4',
        title: '1. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 2 - V5 כתוביות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 3 - V4 כתוביות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 4 - V5.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 5 - V5 כתוביות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 6 - V5 כתויבות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 7 - V4 כתויבות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 8 - V5 כתוביות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 9 - V4 כתויבות.mp4',
        title: '2. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 9 - אנגלית V5.mp4',
        title: '2. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    // {
    //     url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 9 - רוסית - Broom Service - סרטון 9 - V5.mp4',
    //     title: '2. Video Name',
    //     description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    // },
    // {
    //     url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 10 - V4 אנגלית.mp4',
    //     title: '2. Video Name',
    //     lng: 'ru',
    //     description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    // },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 10 - V4 כתוביות.mp4',
        title: '2. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/Broom Service - סרטון 10 - V4 רוסית.mp4',
        title: '2. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    }
];

const VideoGalleryPage = ({
    worker,
    setNextStep,
    nextStep,
    forms = false
}) => {
    const [selectedVideo, setSelectedVideo] = useState(videos[0]);
    const { t } = useTranslation();

    const handleSelect = (video) => {
        setSelectedVideo(video);
        setTimeout(() => {
            videoRef.current?.load();
            videoRef.current?.play();
        }, 100);
    };


    return (
        <div className="py-5">
            <h3 className="mb-4 fw-bold">Tutorials</h3>
            <CustomVideoPlayer src={selectedVideo.url} />

            <div>
                <h5 className="mb-3 fw-semibold">List Of Videos</h5>

                {/* Video Thumbnails */}
                <div className="row g-3">
                    {videos.map((video, index) => (
                        <div className="col-12 col-sm-6 col-md-4 col-lg-3 my-1" key={index}>
                            <div
                                className={`p-3 bg-white rounded shadow-sm h-100 border ${video.url === selectedVideo.url ? 'border-primary' : ''}`}
                                onClick={() => handleSelect(video)}
                                style={{ cursor: 'pointer' }}
                            >
                                {/* Show play icon thumbnail on medium and up */}
                                <div
                                    className="bg-light d-none d-md-flex align-items-center justify-content-center rounded mb-2"
                                    style={{ height: '150px' }}
                                >
                                    <video
                                        src={video.url}
                                        muted
                                        playsInline
                                        className="w-100 h-100 object-fit-cover"
                                    />
                                </div>
                                {/* <h6 className="fw-bold">{video.title}</h6>
                                <p className="small text-muted">{video.description}</p> */}
                            </div>
                        </div>
                    ))}

                </div>
            </div>
            {
                forms && nextStep != 8 && (
                    <div className="d-flex justify-content-end mt-2">
                        <button
                            type="button"
                            onClick={(e) => setNextStep(prev => prev - 1)}
                            className="navyblue py-2 px-4 mr-2"
                            name="prev"
                            style={{ borderRadius: "5px" }}
                        >
                            <GrFormPreviousLink /> {t("common.prev")}
                        </button>
                    </div>
                )
            }
        </div>
    );
};


export default VideoGalleryPage;