import React, { useState, useEffect } from "react";
import axios from "axios";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function Integration() {
    const [isGoogleConnected, setIsGoogleConnected] = useState(false);
    const [calendars, setCalendars] = useState([]);
    const [selectedCalendar, setSelectedCalendar] = useState("");
    const [isLoading, setIsLoading] = useState(false);
    const [role, setRole] = useState("");
    const [email, setEmail] = useState("");
    const alert = useAlert();

    useEffect(() => {
        checkGoogleCalendarStatus();
        getAdmin();
    }, []);

    useEffect(() => {
        if (isGoogleConnected) {
            fetchGoogleCalendarList();
        }
    }, [isGoogleConnected]);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: "Bearer " + localStorage.getItem("admin-token"),
    };

    const checkGoogleCalendarStatus = async () => {
        try {
            const response = await axios.get("/api/admin/google/auth", {
                headers,
            });
            setIsGoogleConnected(response.data.action === "connected");
        } catch (error) {
            console.error("Error:", error);
            alert.error(
                "An error occurred while checking Google Calendar connection."
            );
        }
    };
   
    const handleGoogleCalendarClick = async () => {
        try {
            const response = await axios.get("/api/admin/google/auth", {
                headers,
            });
            console.log("Response Data", response.data);
            if (response.data.action === "redirect") {
                window.location.href = response.data.url;
            } else {
                alert.error("Failed to get Google Calendar URL");
            }
        } catch (error) {
            console.error("Error:", error);
            alert.error(
                "An error occurred while trying to connect to Google Calendar."
            );
        }
    };

    const handleRemoveGoogleCalendar = async () => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to reconnect without re-authentication!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Disconnect Google Calendar!",
        }).then(async (result) => {
            if (result.isConfirmed) {
                setIsLoading(true);

                try {
                    const response = await axios.delete(
                        "/api/admin/google/disconnect",
                        { headers }
                    );
                    setIsLoading(false);

                    if (response.status === 200) {
                        setIsGoogleConnected(false);
                        setCalendars([]);
                        Swal.fire(
                            "Disconnected!",
                            response.data.message ||
                                "Google Calendar has been disconnected.",
                            "success"
                        );
                    } else {
                        throw new Error(
                            "Failed to disconnect Google Calendar."
                        );
                    }
                } catch (error) {
                    setIsLoading(false);
                    Swal.fire({
                        title: "Error!",
                        text:
                            error.response?.data?.message ||
                            "An error occurred while disconnecting Google Calendar.",
                        icon: "error",
                    });
                }
            }
        });
    };

    const fetchGoogleCalendarList = async () => {
        if (!isGoogleConnected) return;

        setIsLoading(true);

        try {
            const response = await axios.get("/api/admin/calendar-list", {
                headers,
            });
            if (response.status === 200) {
                setCalendars(response.data.items || []);
                setSelectedCalendar(response.data.selectedCalendarId || null);
            } else {
                alert.error("Failed to retrieve calendar list.");
            }
        } catch (error) {
            console.error("Error:", error);
            alert.error("An error occurred while fetching calendar list.");
        } finally {
            setIsLoading(false);
        }
    };

    const handleCalendarChange = (e) => {
        setSelectedCalendar(e.target.value);
    };

    const getAdmin = async () => {
        try {
            const res = await axios.get("/api/admin/details", { headers });
            setEmail(res?.data?.success?.email);
            setRole(res?.data?.success?.role);
        } catch (error) {
            console.error("Error fetching admin details:", error);
        }
    };

    const handleSubmitCalendar = async () => {
        if (!selectedCalendar) {
            alert.error("Please select a calendar first.");
            return;
        }

        try {
            const response = await axios.post(
                "/api/admin/calendar/save",
                { calendarId: selectedCalendar, role },
                { headers }
            );
            if (response.status === 200) {
                alert.success("Calendar saved successfully!");
            } else {
                alert.error("Failed to save calendar.");
            }
        } catch (error) {
            console.error("Error saving calendar:", error);
            alert.error("An error occurred while saving the calendar.");
        }
    };

    return (
        <div className="form-group mt-4">
            {role && (role === "superadmin" || role === "hr") && (
                <>
                    <input
                        type="submit"
                        value="Connect Google Account"
                        onClick={handleGoogleCalendarClick}
                        className="btn navyblue saveBtn"
                        disabled={isGoogleConnected}
                    />

                    {isGoogleConnected ? (
                        <>
                            <div className="form-group mt-4">
                                <label htmlFor="calendarSelect">
                                    Select Calendar:
                                </label>

                                {isLoading ? (
                                    <p>Loading calendars...</p>
                                ) : (
                                    <select
                                        id="calendarSelect"
                                        className="form-control"
                                        value={selectedCalendar}
                                        onChange={handleCalendarChange}
                                    >
                                        <option value="">
                                            Select a Calendar
                                        </option>
                                        {calendars.map((calendar) => (
                                            <option
                                                key={calendar.id}
                                                value={calendar.id}
                                            >
                                                {calendar.summary}
                                            </option>
                                        ))}
                                    </select>
                                )}
                            </div>

                            <div className="form-group mt-4 text-center">
                                <input
                                    type="button"
                                    value="Submit Calendar"
                                    onClick={handleSubmitCalendar}
                                    className="btn navyblue mt-2"
                                    disabled={!selectedCalendar || isLoading}
                                />
                            </div>

                            <div className="form-group mt-4 text-center">
                                <input
                                    type="button"
                                    value="Remove Google Account"
                                    onClick={handleRemoveGoogleCalendar}
                                    className="btn red mt-2 text-center"
                                    disabled={isLoading}
                                />
                            </div>
                        </>
                    ) : (
                        <p className="mt-4 text-center">
                            Please connect your google account to schedule a
                            meeting.
                        </p>
                    )}
                </>
            )}
            <FullPageLoader visible={isLoading} />
        </div>
    );
}
