import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Moment from "moment";
import CanvasJSReact from "@canvasjs/react-charts";

import Sidebar from "../Layouts/Sidebar";
import FilterButtons from "../../Components/common/FilterButton";

const thisMonthFilter = {
    start_date: Moment().startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().endOf("month").format("YYYY-MM-DD"),
};

const thisWeekFilter = {
    start_date: Moment().startOf("week").format("YYYY-MM-DD"),
    end_date: Moment().endOf("week").format("YYYY-MM-DD"),
};

export default function income() {
    const todayFilter = {
        start_date: Moment().format("YYYY-MM-DD"),
        end_date: Moment().format("YYYY-MM-DD"),
    };
    const currentWeekFilter = {
        start_date: Moment().startOf("week").format("YYYY-MM-DD"),
        end_date: Moment().endOf("week").format("YYYY-MM-DD"),
    };
    const nextWeekFilter = {
        start_date: Moment()
            .add(1, "weeks")
            .startOf("week")
            .format("YYYY-MM-DD"),
        end_date: Moment().add(1, "weeks").endOf("week").format("YYYY-MM-DD"),
    };
    const previousWeekFilter = {
        start_date: Moment()
            .subtract(1, "weeks")
            .startOf("week")
            .format("YYYY-MM-DD"),
        end_date: Moment()
            .subtract(1, "weeks")
            .endOf("week")
            .format("YYYY-MM-DD"),
    };

    const [totalTask, setTotalTask] = useState(0);
    const [income, setIncome] = useState(0);
    const [totalMins, setTotalMins] = useState(0);
    const [totalActualMins, setTotalActualMins] = useState(0);
    const [totalDiffMins, setTotalDiffMins] = useState(0);
    const [role, setRole] = useState();
    const [incomeDataPoints, setIncomeDataPoints] = useState([]);
    const [outcomeDataPoints, setOutcomeDataPoints] = useState([]);
    const [selectedFilter, setselectedFilter] = useState("Week");
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });

    const navigate = useNavigate();
    const CanvasJSChart = CanvasJSReact.CanvasJSChart;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getTasks = () => {
        axios
            .post("/api/admin/income", { ...dateRange }, { headers })
            .then((res) => {
                setTotalTask(res.data.data.total_jobs);
                setIncome(res.data.data.income);
                setTotalMins(res.data.data.duration_minutes);
                setTotalActualMins(res.data.data.actual_time_taken_minutes);
                setTotalDiffMins(res.data.data.difference_minutes);

                const _graph = res.data.graph;

                if (_graph.labels) {
                    let _incomePoints = [];
                    let _outcomePoints = [];
                    for (let index = 0; index < _graph.labels.length; index++) {
                        _incomePoints.push({
                            y: _graph.data.profit[index],
                            label: _graph.labels[index],
                        });

                        _outcomePoints.push({
                            y: _graph.data.expense[index],
                            label: _graph.labels[index],
                        });
                    }

                    setIncomeDataPoints(_incomePoints);
                    setOutcomeDataPoints(_outcomePoints);
                }
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

    const chartOptions = {
        title: {
            text: "Overview",
        },
        // subtitles: [
        //     {
        //         text: "GBP & USD to INR",
        //     },
        // ],
        axisY: {
            prefix: "₪ ",
        },
        toolTip: {
            shared: true,
        },
        data: [
            {
                type: "line",
                name: "Income",
                // showInLegend: true,
                yValueFormatString: "₪ #,##0.##",
                dataPoints: incomeDataPoints,
            },
            {
                type: "line",
                name: "Outcome",
                // showInLegend: false,
                yValueFormatString: "₪ #,##0.##",
                dataPoints: outcomeDataPoints,
            },
        ],
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
                <div className="titleBox">
                    <div className="row">
                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                            <div className="d-flex align-items-center">
                                <div
                                    style={{ fontWeight: "bold" }}
                                    className="mr-2"
                                >
                                    Date Period
                                </div>
                                <FilterButtons
                                    text="Day"
                                    className="px-4 mr-1"
                                    onClick={() =>
                                        setDateRange({
                                            start_date: todayFilter.start_date,
                                            end_date: todayFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedFilter}
                                    setselectedFilter={setselectedFilter}
                                />
                                <FilterButtons
                                    text="Week"
                                    className="px-4 mr-3"
                                    onClick={() =>
                                        setDateRange({
                                            start_date:
                                                currentWeekFilter.start_date,
                                            end_date:
                                                currentWeekFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedFilter}
                                    setselectedFilter={setselectedFilter}
                                />
                                <FilterButtons
                                    text="Previous Week"
                                    className="px-3 mr-1"
                                    onClick={() =>
                                        setDateRange({
                                            start_date:
                                                previousWeekFilter.start_date,
                                            end_date:
                                                previousWeekFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedFilter}
                                    setselectedFilter={setselectedFilter}
                                />
                                <FilterButtons
                                    text="Next Week"
                                    className="px-3"
                                    onClick={() =>
                                        setDateRange({
                                            start_date:
                                                nextWeekFilter.start_date,
                                            end_date: nextWeekFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedFilter}
                                    setselectedFilter={setselectedFilter}
                                />
                            </div>
                        </div>
                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between my-2">
                            <div className="d-flex align-items-center">
                                <div
                                    className="mr-3"
                                    style={{ fontWeight: "bold" }}
                                >
                                    Custom Date Range
                                </div>

                                <input
                                    className="form-control"
                                    type="date"
                                    placeholder="From date"
                                    name="from filter"
                                    style={{ width: "fit-content" }}
                                    value={dateRange.start_date}
                                    onChange={(e) => {
                                        setselectedFilter("Custom Range");
                                        setDateRange({
                                            start_date: e.target.value,
                                            end_date: dateRange.end_date,
                                        });
                                    }}
                                />
                                <div className="mx-2">to</div>
                                <input
                                    className="form-control"
                                    type="date"
                                    placeholder="To date"
                                    name="to filter"
                                    style={{ width: "fit-content" }}
                                    value={dateRange.end_date}
                                    onChange={(e) => {
                                        setselectedFilter("Custom Range");
                                        setDateRange({
                                            start_date: dateRange.start_date,
                                            end_date: e.target.value,
                                        });
                                    }}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="row adminDash">
                        <div className="col-lg-4 col-sm-6  col-xs-6">
                            <div className="dashBox">
                                <div className="dashIcon">
                                    <i className="fa-solid fa-suitcase"></i>
                                </div>
                                <div className="dashText">
                                    <h3>{totalTask}</h3>
                                    <p>Total Jobs</p>
                                </div>
                            </div>
                        </div>

                        <div className="col-lg-4 col-sm-6  col-xs-6">
                            <div className="dashBox">
                                <div className="dashIcon">
                                    <i className="fa-solid fa-suitcase"></i>
                                </div>
                                <div className="dashText">
                                    <h3>{minutesToHours(totalMins)}</h3>
                                    <p>Total Job Hours</p>
                                </div>
                            </div>
                        </div>

                        <div className="col-lg-4 col-sm-6  col-xs-6">
                            <div className="dashBox">
                                <div className="dashIcon">
                                    <i className="fa-solid fa-suitcase"></i>
                                </div>
                                <div className="dashText">
                                    <h3>{minutesToHours(totalActualMins)}</h3>
                                    <p>Total Actual Hours</p>
                                </div>
                            </div>
                        </div>

                        <div className="col-lg-4 col-sm-6  col-xs-6">
                            <div className="dashBox">
                                <div className="dashIcon">
                                    <i className="fa-solid fa-suitcase"></i>
                                </div>
                                <div className="dashText">
                                    <h3>{minutesToHours(totalDiffMins)}</h3>
                                    <p>Total Exceed Hours</p>
                                </div>
                            </div>
                        </div>

                        {/* <div className="col-lg-4 col-sm-6  col-xs-6">
                            <div className="dashBox">
                                <div className="dashIcon">
                                    <i className="fa-solid fa-suitcase"></i>
                                </div>
                                <div className="dashText">
                                    <h3>{income}</h3>
                                    <p>Income</p>
                                </div>
                            </div>
                        </div>

                        <div className="col-lg-4 col-sm-6  col-xs-6">
                            <div className="dashBox">
                                <div className="dashIcon">
                                    <i className="fa-solid fa-suitcase"></i>
                                </div>
                                <div className="dashText">
                                    <h3>0</h3>
                                    <p>Outcome</p>
                                </div>
                            </div>
                        </div> */}
                    </div>

                    <div className="card">
                        <div className="card-body">
                            <div className="row">
                                <div className="col-md-12">
                                    <CanvasJSChart options={chartOptions} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
