import React, { useState, useRef } from 'react';
import "./Video.css"
import CustomVideoPlayer from './CustomVideoPlayer';
import { GrFormPreviousLink } from 'react-icons/gr';
import { useTranslation } from 'react-i18next';

const videos = [
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+1-%D7%91%D7%A8%D7%95%D7%9B%D7%99%D7%9D+%D7%94%D7%91%D7%90%D7%99%D7%9D+%D7%9C%D7%91%D7%A8%D7%95%D7%9D+%D7%A1%D7%A8%D7%95%D7%95%D7%99%D7%A1.mp4',
        title: '1. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+2-%D7%9E%D7%98%D7%91%D7%97.mp4',
        title: '2. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+3-%D7%97%D7%93%D7%A8%D7%99+%D7%90%D7%9E%D7%91%D7%98%D7%99%D7%94.mp4',
        title: '3. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+4-%D7%97%D7%93%D7%A8+%D7%A9%D7%99%D7%A0%D7%94%2C+%D7%A1%D7%99%D7%93%D7%95%D7%A8+%D7%9E%D7%99%D7%98%D7%94+%D7%95%D7%A7%D7%99%D7%A4%D7%95%D7%9C.mp4',
        title: '4. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+5-%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F+%D7%94%D7%A1%D7%9C%D7%95%D7%9F+%D7%95%D7%94%D7%9E%D7%A8%D7%A4%D7%A1%D7%AA.mp4',
        title: '5. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+6-%D7%A0%D7%99%D7%A7%D7%95%D7%99+%D7%90%D7%91%D7%A7+%D7%95%D7%A8%D7%A6%D7%A4%D7%95%D7%AA.mp4',
        title: '6. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/english/%D7%A4%D7%A8%D7%A7+7-%D7%A1%D7%99%D7%95%D7%9D+%D7%94%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F.mp4',
        title: '7. Video Name',
        lng: 'en',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+1-%D7%91%D7%A8%D7%95%D7%9B%D7%99%D7%9D+%D7%94%D7%91%D7%90%D7%99%D7%9D+%D7%9C%D7%91%D7%A8%D7%95%D7%9D+%D7%A1%D7%A8%D7%95%D7%95%D7%99%D7%A1.mp4',
        title: '8. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+2-%D7%9E%D7%98%D7%91%D7%97.mp4',
        title: '9. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+3-%D7%97%D7%93%D7%A8%D7%99+%D7%90%D7%9E%D7%91%D7%98%D7%99%D7%94.mp4',
        title: '10. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+4-%D7%97%D7%93%D7%A8+%D7%A9%D7%99%D7%A0%D7%94%2C+%D7%A1%D7%99%D7%93%D7%95%D7%A8+%D7%9E%D7%99%D7%98%D7%94+%D7%95%D7%A7%D7%99%D7%A4%D7%95%D7%9C.mp4',
        title: '11. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+5-%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F+%D7%94%D7%A1%D7%9C%D7%95%D7%9F+%D7%95%D7%94%D7%9E%D7%A8%D7%A4%D7%A1%D7%AA.mp4',
        title: '12. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+6-%D7%A0%D7%99%D7%A7%D7%95%D7%99+%D7%90%D7%91%D7%A7+%D7%95%D7%A8%D7%A6%D7%A4%D7%95%D7%AA.mp4',
        title: '13. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/hebrew/%D7%A4%D7%A8%D7%A7+7-%D7%A1%D7%99%D7%95%D7%9D+%D7%94%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F.mp4',
        title: '14. Video Name',
        lng: 'heb',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+1-%D7%91%D7%A8%D7%95%D7%9B%D7%99%D7%9D+%D7%94%D7%91%D7%90%D7%99%D7%9D+%D7%9C%D7%91%D7%A8%D7%95%D7%9D+%D7%A1%D7%A8%D7%95%D7%95%D7%99%D7%A1.mp4',
        title: '15. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+2-%D7%9E%D7%98%D7%91%D7%97.mp4',
        title: '16. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+3-%D7%97%D7%93%D7%A8%D7%99+%D7%90%D7%9E%D7%91%D7%98%D7%99%D7%94.mp4',
        title: '17. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+5-%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F+%D7%94%D7%A1%D7%9C%D7%95%D7%9F+%D7%95%D7%94%D7%9E%D7%A8%D7%A4%D7%A1%D7%AA.mp4',
        title: '18. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+5-%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F+%D7%94%D7%A1%D7%9C%D7%95%D7%9F+%D7%95%D7%94%D7%9E%D7%A8%D7%A4%D7%A1%D7%AA.mp4',
        title: '19. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+6-%D7%A0%D7%99%D7%A7%D7%95%D7%99+%D7%90%D7%91%D7%A7+%D7%95%D7%A8%D7%A6%D7%A4%D7%95%D7%AA.mp4',
        title: '20. Video Name',
        lng: 'ru',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
    },
    {
        url: 'https://broomservice-tutorials.s3.eu-west-1.amazonaws.com/russian/%D7%A4%D7%A8%D7%A7+7-%D7%A1%D7%99%D7%95%D7%9D+%D7%94%D7%A0%D7%99%D7%A7%D7%99%D7%95%D7%9F.mp4',
        title: '21. Video Name',
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

    let filteredVideos = videos.filter(video => video.lng == worker?.lng);

    if (filteredVideos.length === 0) {
        filteredVideos = videos.filter(video => video.lng === 'en');
    }

    return (
        <div className="py-5">
            <h3 className="mb-4 fw-bold">{t('worker.tutorials')}</h3>
            <CustomVideoPlayer src={selectedVideo.url} />

            <div>
                <h5 className="mb-3 fw-semibold">{t('worker.list_of_videos')}</h5>

                <div className="row g-3">
                    {filteredVideos.map((video, index) => {
                        return (
                            <div className="col-12 col-sm-6 col-md-4 col-lg-3 my-1" key={index}>
                                <div
                                    className={`p-3 bg-white rounded shadow-sm h-100 border ${video.url === selectedVideo.url ? 'border-primary' : ''}`}
                                    onClick={() => handleSelect(video)}
                                    style={{ cursor: 'pointer' }}
                                >
                                    {/* Desktop video preview */}
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

                                    {/* Mobile preview - either same video or static image (optional) */}
                                    <div
                                        className="d-block d-md-none mb-2 rounded overflow-hidden"
                                        style={{ height: '150px' }}
                                    >
                                        <video
                                            src={video.url}
                                            muted
                                            playsInline
                                            className="w-100 h-100 object-fit-cover"
                                        />
                                    </div>

                                    {/* Optional Title */}
                                    {/* <h6 className="fw-bold">{video.title}</h6> */}
                                </div>
                            </div>
                        );
                    })}
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