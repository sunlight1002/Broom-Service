import React, { useRef, useState, useEffect } from 'react';
import { AiFillSound } from "react-icons/ai";
import { FaPlay, FaPause } from "react-icons/fa";

const CustomVideoPlayer = ({ src }) => {
    const videoRef = useRef(null);
    const [playing, setPlaying] = useState(false);
    const [volume, setVolume] = useState(1);
    const [progress, setProgress] = useState(0);
    const [duration, setDuration] = useState(0);

    const togglePlay = () => {
        const video = videoRef.current;
        if (video.paused) {
            video.play();
            setPlaying(true);
        } else {
            video.pause();
            setPlaying(false);
        }
    };

    const handleVolumeChange = (e) => {
        const newVolume = parseFloat(e.target.value);
        setVolume(newVolume);
        videoRef.current.volume = newVolume;
    };

    const handleProgressChange = (e) => {
        const newTime = parseFloat(e.target.value);
        videoRef.current.currentTime = newTime;
        setProgress(newTime);
    };

    useEffect(() => {
        const video = videoRef.current;
        let animationFrameId;

        const setVideoDuration = () => setDuration(video.duration);

        const updateProgress = () => {
            setProgress(video.currentTime);
            animationFrameId = requestAnimationFrame(updateProgress);
        };

        const handlePlay = () => {
            animationFrameId = requestAnimationFrame(updateProgress);
        };

        const handlePauseOrEnd = () => {
            cancelAnimationFrame(animationFrameId);
        };

        video.addEventListener('loadedmetadata', setVideoDuration);
        video.addEventListener('play', handlePlay);
        video.addEventListener('pause', handlePauseOrEnd);
        video.addEventListener('ended', handlePauseOrEnd);

        return () => {
            video.removeEventListener('loadedmetadata', setVideoDuration);
            video.removeEventListener('play', handlePlay);
            video.removeEventListener('pause', handlePauseOrEnd);
            video.removeEventListener('ended', handlePauseOrEnd);
            cancelAnimationFrame(animationFrameId);
        };
    }, []);

    useEffect(() => {
        if (videoRef.current) {
            videoRef.current.load(); // reload new video
            videoRef.current.play(); // auto-play
            setPlaying(true);        // update UI
        }
    }, [src]);
    

    return (
        <div className="video-player-container">
            <div style={{
                height: '500px',
            }}>
                <video
                    ref={videoRef}
                    className="video-player"
                    src={src}
                    preload="metadata"
                >
                    Your browser does not support the video tag.
                </video>
            </div>

            {/* Custom Controls */}
            <div className="custom-controls row m-0 pb-2">
                <div className="progress-bar-container col-12 col-md-7 mt-3">
                    <div className='d-flex justify-content-between align-items-center ml-3 w-100'>
                        <span className="progress-time">{formatTime(progress)}</span>
                        <span className="progress-time">{formatTime(duration)}</span>
                    </div>
                    <input
                        type="range"
                        min="0"
                        max={duration || 0}
                        value={progress}
                        step="0.01"
                        onChange={handleProgressChange}
                        className="progress-bar mx-3 my-1"
                        style={{
                            '--progress': `${(progress / duration) * 100}%`
                        }}
                    />
                </div>

                <div className="controls-right col-12 col-md-5 d-flex justify-content-between align-items-center mt-2">
                    <div className="volume-container w-100 d-flex justify-content-end">
                        <div className='d-flex justify-content-center align-items-center'>
                            <AiFillSound className='font-30' />
                        </div>
                        <input
                            type="range"
                            min="0"
                            max="1"
                            step="0.01"
                            value={volume}
                            onChange={handleVolumeChange}
                            className="volume-slider mx-3 my-4"
                            style={{
                                '--volume': `${volume * 100}%`
                            }}
                        />
                    </div>
                    <button onClick={togglePlay} className="play-pause-btn mx-2 navyblue">
                        {playing ? <FaPause className='font-22 text-white' /> : <FaPlay className='font-22 text-white' />}
                    </button>
                </div>
            </div>
        </div>
    );
};

const formatTime = (time) => {
    const mins = Math.floor(time / 60);
    const secs = Math.floor(time % 60);
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
};

export default CustomVideoPlayer;
