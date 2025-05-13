import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import { useParams } from 'react-router-dom';

const LeadActivityList = () => {
    const { id: clientId } = useParams();
    const [leadActivities, setLeadActivities] = useState([]);
    const tableRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
    };

    useEffect(() => {
        if (clientId) {
            axios
                .get(`/api/admin/lead-activities/${clientId}`, { headers })
                .then((res) => {
                    const sortedData = res.data
                        .map(activity => ({
                            ...activity,
                            created_date: activity.created_date ? new Date(activity.created_date) : null,
                            status_changed_date: activity.status_changed_date ? new Date(activity.status_changed_date) : null
                        }))
                        // .sort((a, b) => a.created_date - b.created_date);

                    setLeadActivities(sortedData);
                })
                .catch((error) => {
                    console.error('Error fetching lead activities:', error);
                });
        }
    }, [clientId]);

    return (
        <div className="mr-0 mr-md-8 p-md-4 p-0">
            <h5 className="mb-3">Lead Activities</h5>
            <div className="overflow-x-auto">
                <table ref={tableRef} className="display table table-bordered w-100">
                    <tbody>
                        {leadActivities.length > 0 && leadActivities[0].created_date instanceof Date && !isNaN(leadActivities[0].created_date) && (
                            <tr className="bg-gray-50">
                                <td className="px-10 py-3 border-b text-left">
                                    Lead created on {leadActivities[0].created_date.toLocaleString()} with status "{leadActivities[0].changes_status || 'pending'}"
                                </td>
                            </tr>
                        )}

                        {leadActivities.length > 0 && leadActivities.map((activity, index) => {
                            let statusChangeMessage = '';
                            const previousActivity = index > 0 ? leadActivities[index - 1] : null;
                            const oldStatus = previousActivity ? previousActivity.changes_status : 'pending';
                            if (!activity.status_changed_date || activity.changes_status === oldStatus) {
                                return null;
                            }

                            statusChangeMessage = `Status changed on ${activity.status_changed_date.toLocaleString()} from "${oldStatus}" to "${activity.changes_status}"`;
                            if(activity?.changed_by){
                                statusChangeMessage += ` changed by ${activity.changed_by}`
                            }

                            return (
                                <tr key={activity.id} className="hover:bg-gray-100">
                                    <td className="px-10 py-3 border-b text-left">
                                    <p>{statusChangeMessage}</p>
                                    <p><strong>Reason:</strong> {activity.reason}</p>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default LeadActivityList;
