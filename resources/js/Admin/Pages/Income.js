import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Moment from "moment";
import CanvasJSReact from "@canvasjs/react-charts";

import Sidebar from "../Layouts/Sidebar";
import FilterButtons from "../../Components/common/FilterButton";
import FullPageLoader from "../../Components/common/FullPageLoader";

export default function income() {
    const [totalTask, setTotalTask] = useState(0);
    const [income, setIncome] = useState(0);
    const [totalMins, setTotalMins] = useState(0);
    const [totalActualMins, setTotalActualMins] = useState(0);
    const [totalDiffMins, setTotalDiffMins] = useState(0);
    const [role, setRole] = useState();
    const [incomeDataPoints, setIncomeDataPoints] = useState([]);
    const [outcomeDataPoints, setOutcomeDataPoints] = useState([]);
    const [selectedDateRange, setSelectedDateRange] = useState("Week");
    const [selectedDateStep, setSelectedDateStep] = useState("Current");
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });
    const [isLoading, setIsLoading] = useState(false);

    const navigate = useNavigate();
    const CanvasJSChart = CanvasJSReact.CanvasJSChart;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getTasks = () => {
        setIsLoading(true);

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
                setIsLoading(false);
            })
            .catch((e) => {
                setIsLoading(false);
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
        subtitles: [
            {
                text: `${dateRange.start_date} to ${dateRange.end_date}`,
            },
        ],
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

    useEffect(() => {
        let _startMoment = Moment();
        let _endMoment = Moment();
        if (selectedDateRange == "Day") {
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "day");
                _endMoment.subtract(1, "day");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "day");
                _endMoment.add(1, "day");
            }
        } else if (selectedDateRange == "Week") {
            _startMoment.startOf("week");
            _endMoment.endOf("week");
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "week");
                _endMoment.subtract(1, "week");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "week");
                _endMoment.add(1, "week");
            }
        } else if (selectedDateRange == "Month") {
            _startMoment.startOf("month");
            _endMoment.endOf("month");
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "month");
                _endMoment.subtract(1, "month");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "month");
                _endMoment.add(1, "month");
            }
        } else if (selectedDateRange == "Year") {
            _startMoment.startOf("year");
            _endMoment.endOf("year");
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "year");
                _endMoment.subtract(1, "year");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "year");
                _endMoment.add(1, "year");
            }
        } else {
            _startMoment = Moment("2000-01-01");
        }

        setDateRange({
            start_date: _startMoment.format("YYYY-MM-DD"),
            end_date: _endMoment.format("YYYY-MM-DD"),
        });
    }, [selectedDateRange, selectedDateStep]);

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
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />
                                <FilterButtons
                                    text="Week"
                                    className="px-4 mr-1"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />
                                <FilterButtons
                                    text="Month"
                                    className="px-4 mr-1"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />
                                <FilterButtons
                                    text="Year"
                                    className="px-4 mr-1"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />
                                <FilterButtons
                                    text="All Time"
                                    className="px-4 mr-3"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />
                                {selectedDateRange !== "All Time" && (
                                    <>
                                        <FilterButtons
                                            text="Previous"
                                            className="px-3 mr-1"
                                            selectedFilter={selectedDateStep}
                                            setselectedFilter={
                                                setSelectedDateStep
                                            }
                                        />
                                        <FilterButtons
                                            text="Current"
                                            className="px-3 mr-1"
                                            selectedFilter={selectedDateStep}
                                            setselectedFilter={
                                                setSelectedDateStep
                                            }
                                        />
                                        <FilterButtons
                                            text="Next"
                                            className="px-3"
                                            selectedFilter={selectedDateStep}
                                            setselectedFilter={
                                                setSelectedDateStep
                                            }
                                        />
                                    </>
                                )}
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

            <FullPageLoader visible={isLoading} />
        </div>
    );
}
