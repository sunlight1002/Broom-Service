import React, { useState, useEffect, useRef } from 'react';
import { useAlert } from 'react-alert';
import axios from 'axios';
import { getLocationAndAddress } from '../../Utils/common.utils';

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


    const handleStartStop = async () => {
        if (!isRunning) {
            // Starting
            try {
                const { address, latitude, longitude } = await getLocationAndAddress(alert);
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
            } catch (err) {
                console.log('Timer not started due to location issue.', err);
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

    useEffect(() => {
        const fetchLastTimer = async () => {
            const adminId = localStorage.getItem("admin-id"); 
            if (!adminId) return;

            try {
                const res = await axios.get(`/api/admin/last-time-log/${adminId}`, { headers });
                const timerLog = res?.data?.timerLog;

                if (timerLog && timerLog.start_timer && !timerLog.end_timer) {
                    const startTime = new Date(timerLog.start_timer);
                    const elapsed = Math.floor((Date.now() - startTime.getTime()) / 1000);

                    localStorage.setItem('admin-timer-log-id', timerLog.id);
                    localStorage.setItem('admin-timer-start-time', startTime.toISOString());

                    setTime(elapsed > 0 ? elapsed : 0);
                    setIsRunning(true);
                }else{
                    localStorage.removeItem('admin-timer-log-id');
                    localStorage.removeItem('admin-timer-start-time');
                    setIsRunning(false);
                    setTime(0);
                }
            } catch (error) {
                console.error("Error fetching last timer log", error);
            }
        };

        fetchLastTimer();
    }, []);


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
