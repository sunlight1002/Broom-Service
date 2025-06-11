import React, { useState, useEffect, useRef } from 'react';
import { useAlert } from 'react-alert';
import axios from 'axios';

const HeaderTimer = () => {
    const [time, setTime] = useState(0);
    const [isRunning, setIsRunning] = useState(false);
    const [startAddress, setStartAddress] = useState(null);
    const [endAddress, setEndAddress] = useState(null);
    const intervalRef = useRef(null);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        // On mount, check if timer was running
        const savedLogId = localStorage.getItem('admin-timer-log-id');
        const savedStartTime = localStorage.getItem('admin-timer-start-time');

        if (savedLogId && savedStartTime) {
            setIsRunning(true);
            const elapsed = Math.floor((Date.now() - new Date(savedStartTime)) / 1000);
            setTime(elapsed > 0 ? elapsed : 0);
        }
    }, []);

    useEffect(() => {
        if (isRunning) {
            intervalRef.current = setInterval(() => {
                setTime(prevTime => prevTime + 1);
            }, 1000);
        } else {
            clearInterval(intervalRef.current);
        }

        return () => clearInterval(intervalRef.current);
    }, [isRunning]);

    const formatTime = (seconds) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes
                .toString()
                .padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const getLocationAndAddress = async () => {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                alert.error('Geolocation is not supported by your browser.');
                return reject('Geolocation not supported');
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const { latitude, longitude } = position.coords;

                    try {
                        const res = await fetch(
                            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`
                        );
                        const data = await res.json();
                        const address = data.display_name || 'Unknown location';

                        resolve({ address, latitude, longitude });
                    } catch (err) {
                        console.error('Failed to fetch address:', err);
                        reject(err);
                    }
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    alert.error('Location permission denied. Timer cannot be started.');
                    reject(error);
                }
            );
        });
    };

    const handleStartStop = async () => {
        if (!isRunning) {
            // Starting
            try {
                const { address, latitude, longitude } = await getLocationAndAddress();
                setStartAddress(address);

                const res = await handleAddtimer({
                    action: 'start',
                    start_location: address,
                    start_lat: latitude,
                    start_lng: longitude,
                });

                if (res?.data?.log_id) {
                    localStorage.setItem('admin-timer-log-id', res.data.log_id);
                    localStorage.setItem('admin-timer-start-time', new Date().toISOString());
                    setIsRunning(true);
                }

                console.log('🟢 Start Address:', address);
            } catch (err) {
                console.log('Timer not started due to location issue.');
            }
        } else {
            // Stopping
            try {
                const { address, latitude, longitude } = await getLocationAndAddress();
                setEndAddress(address);

                await handleAddtimer({
                    action: 'stop',
                    end_location: address,
                    end_lat: latitude,
                    end_lng: longitude,
                });

                localStorage.removeItem('admin-timer-log-id');
                localStorage.removeItem('admin-timer-start-time');
                setIsRunning(false);
                setTime(0);
                console.log('🔴 End Address:', address);
            } catch (err) {
                console.log('Failed to fetch end address.');
            }
        }
    };

    const handleAddtimer = async (data) => {
        try {
            const res = await axios.post('/api/admin/add-time-logs', data, { headers });
            alert.success(res?.data?.message);
            return res;
        } catch (err) {
            console.error('Failed to add timer:', err);
            alert.error('Failed to log time. Please try again.');
            return null;
        }
    };

    return (
        <>
            <style jsx>{`
                .status-indicator {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background-color: #4CAF50;
                    animation: ${isRunning ? 'pulse 2s infinite' : 'none'};
                }
            `}
            </style>
            <div className="timer-container">
                <div className="timer-display">{formatTime(time)}</div>

                <button
                    className={`timer-btn ${isRunning ? 'stop-btn' : 'start-btn'}`}
                    onClick={handleStartStop}
                >
                    {isRunning ? 'Stop' : 'Start'}
                </button>

                <div className="timer-status d-none d-md-flex">
                    <div className={`status-indicator ${!isRunning ? 'stopped' : ''}`}></div>
                    <span>{isRunning ? 'Running' : 'Stopped'}</span>
                </div>
            </div>
        </>
    );
};

export default HeaderTimer;
