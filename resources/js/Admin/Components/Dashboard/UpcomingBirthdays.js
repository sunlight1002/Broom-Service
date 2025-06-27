import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';

export default function UpcomingBirthdays() {
    const { t } = useTranslation();
    const [upcomingBirthdays, setUpcomingBirthdays] = useState([]);
    const [loading, setLoading] = useState(true);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        fetchUpcomingBirthdays();
    }, []);

    const fetchUpcomingBirthdays = async () => {
        try {
            const response = await axios.get('/api/admin/workers/upcoming-birthdays', { headers });
            setUpcomingBirthdays(response.data.upcoming_birthdays || []);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching upcoming birthdays:', error);
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
        });
    };

    if (loading) {
        return (
            <div className="dashBox">
                <h3>🎂 {t("admin.dashboard.upcoming_birthdays") || "Upcoming Birthdays"}</h3>
                <p>Loading...</p>
            </div>
        );
    }

    return (
        <div className="dashBox">
            <h3>🎂 {t("admin.dashboard.upcoming_birthdays") || "Upcoming Birthdays"}</h3>
            {upcomingBirthdays.length > 0 ? (
                <div className="upcoming-birthdays-list">
                    {upcomingBirthdays.slice(0, 5).map((worker, index) => (
                        <div key={worker.id} className="birthday-item">
                            <div className="worker-info">
                                <strong>{worker.name}</strong>
                                <span className="birthday-date">
                                    {formatDate(worker.upcoming_birthday)}
                                </span>
                            </div>
                            <div className="birthday-status">
                                {worker.is_today ? (
                                    <span className="badge badge-success">Today! 🎉</span>
                                ) : (
                                    <span className="badge badge-info">
                                        {worker.days_until_birthday} day{worker.days_until_birthday !== 1 ? 's' : ''} away
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                    {upcomingBirthdays.length > 5 && (
                        <div className="text-center mt-2">
                            <small className="text-muted">
                                +{upcomingBirthdays.length - 5} more birthdays
                            </small>
                        </div>
                    )}
                </div>
            ) : (
                <p className="text-muted">No upcoming birthdays in the next month.</p>
            )}
        </div>
    );
} 