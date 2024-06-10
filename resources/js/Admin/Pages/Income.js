import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import Sidebar from "../Layouts/Sidebar";
import axios from "axios";
import Moment from "moment";

const thisMonthFilter = {
    start_date: Moment().startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().endOf("month").format("YYYY-MM-DD"),
};

const todayFilter = {
    start_date: Moment().format("YYYY-MM-DD"),
    end_date: Moment().format("YYYY-MM-DD"),
};

const thisWeekFilter = {
    start_date: Moment().startOf("week").format("YYYY-MM-DD"),
    end_date: Moment().endOf("week").format("YYYY-MM-DD"),
};

export default function income() {
    const [totalTask, setTotalTask] = useState(0);
    const [income, setIncome] = useState(0);
    const [totalMins, setTotalMins] = useState(0);
    const [totalActualMins, setTotalActualMins] = useState(0);
    const [totalDiffMins, setTotalDiffMins] = useState(0);
    const [role, setRole] = useState();

    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const [dateRange, setDateRange] = useState({
        start_date: null,
        end_date: null,
    });

    const getTasks = () => {
        axios
            .post("/api/admin/income", { dateRange }, { headers })
            .then((res) => {
                setTotalTask(res.data.data.total_jobs);
                setIncome(res.data.data.income);
                setTotalMins(res.data.data.duration_minutes);
                setTotalActualMins(res.data.data.actual_time_taken_minutes);
                setTotalDiffMins(res.data.data.difference_minutes);
            });
    };

    const getAdmin = () => {
        axios.get(`/api/admin/details`, { headers }).then((res) => {
            setRole(res.data.success.role);
        });
    };

    const minutesToHours = (minutes) => {
        const hours = Math.floor(minutes / 60);
        return `${hours} hours`;
    };

    useEffect(() => {
        getTasks();
    }, [dateRange]);

    useEffect(() => {
        getAdmin();
        if (role == "member") {
            navigate("/admin/dashboard");
        }
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox card card-body p-3 m-3">
                    <div className="row">
                        <div className="col-sm-6">
                            <h4 className="page-title">
                                Total Jobs : {totalTask}
                            </h4>
                            <h4 className="page-title">
                                Total Job Hours : {minutesToHours(totalMins)}
                            </h4>
                            <h4 className="page-title">
                                Total Actual Hours :{" "}
                                {minutesToHours(totalActualMins)}
                            </h4>
                            <h4 className="page-title">
                                Total Exceed Hours :{" "}
                                {minutesToHours(totalDiffMins)}
                            </h4>
                            <h4 className="page-title">Income : {income}</h4>
                            <h4 className="page-title">Outcome : 0</h4>
                        </div>
                        <div className="col-sm-6">
                            <div
                                className="search-data"
                                style={{ cursor: "pointer" }}
                            >
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date: todayFilter.start_date,
                                            end_date: todayFilter.end_date,
                                        });
                                    }}
                                >
                                    Day
                                </span>
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date:
                                                thisWeekFilter.start_date,
                                            end_date: thisWeekFilter.end_date,
                                        });
                                    }}
                                >
                                    Week
                                </span>
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date:
                                                thisMonthFilter.start_date,
                                            end_date: thisMonthFilter.end_date,
                                        });
                                    }}
                                >
                                    Month
                                </span>
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date: null,
                                            end_date: null,
                                        });
                                    }}
                                >
                                    All
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-12 d-sm-flex flex-wrap align-items-center">
                            <div
                                className="mr-3 "
                                style={{ fontWeight: "bold" }}
                            >
                                Date Period
                            </div>
                            <input
                                className="form-control"
                                type="date"
                                placeholder="From date"
                                name="from filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.start_date}
                                onChange={(e) => {
                                    setDateRange({
                                        start_date: e.target.value,
                                        end_date: dateRange.end_date,
                                    });
                                }}
                            />
                            <div className="mx-2">to</div>
                            <input
                                className="form-control mr-2"
                                type="date"
                                placeholder="To date"
                                name="to filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.end_date}
                                onChange={(e) => {
                                    setDateRange({
                                        start_date: dateRange.start_date,
                                        end_date: e.target.value,
                                    });
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
