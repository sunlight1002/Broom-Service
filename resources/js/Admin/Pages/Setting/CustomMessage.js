import React, { useState, useEffect } from 'react'
import Sidebar from '../../Layouts/Sidebar';
import { useTranslation } from "react-i18next";
import FilterButtons from '../../../Components/common/FilterButton';
import useWindowWidth from '../../../Hooks/useWindowWidth';
import { useAlert } from "react-alert";
import Select from "react-select";

const CustomMessage = () => {
    const { t, i18n } = useTranslation();
    const [type, setType] = useState("")
    const [filter, setFilter] = useState("all");
    const [status, setStatus] = useState("all");
    const [show, setShow] = useState(false)
    const [allWorkers, setAllWorkers] = useState([]);
    const [allClients, setAllClients] = useState([])
    const [workers, setWorkers] = useState([]);
    const [clients, setClients] = useState([])
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });
    const windowWidth = useWindowWidth()
    const [templates, setTemplates] = useState({
        message_heb: "",
        message_en: "",
    });

    useEffect(() => {
        if (windowWidth < 768) {
            setShow(true)
        } else {
            setShow(false)
        }
    }, [windowWidth])


    const leadStatuses = [
        t("admin.leads.Pending"),
        t("admin.leads.Potential"),
        t("admin.leads.Irrelevant"),
        t("admin.leads.Uninterested"),
        t("admin.leads.Unanswered"),
        t("admin.leads.Potential_client"),
        t("admin.leads.Reschedule_call"),
    ];

    const clientStatuses = [
        t("admin.client.Potential"),
        t("admin.client.Pending_client"),
        t("admin.client.Active_client"),
        t("admin.client.Freeze_client"),
        t("admin.client.Past_client"),
    ];
    const workerStatuses = [
        t("admin.global.active"),
        t("admin.global.inactive"),
    ];

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        const selectedWorkerIds = workers.map(worker => worker.value);
        const selectedClientsIds = clients.map(client => client.value);

        const data = {
            type, // 'leads', 'clients', or 'workers'
            status: status !== "Past client" ? status.toLowerCase() : "past",
            worker_ids: selectedWorkerIds, // Include selected workers
            client_ids: selectedClientsIds,
            templates
        };

        try {
            const response = await axios.post(`/api/admin/custom-message-send`, data, { headers });
            if (response.status === 200) {
                console.log(response.data.data);
                reset()

                Swal.fire({
                    icon: 'success',
                    title: t("global.success"),
                    text: t("global.data_fetched"),
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: t("global.error"),
                text: error.response?.data?.message || t("global.fetch_failed"),
            });
        }
    };


    const getWorkers = () => {
        axios.get("/api/admin/all-workers", { headers }).then((res) => {
            const { workers } = res.data;
            const mapWorkersArr = workers.map((w) => {
                let obj = {
                    value: w.id,
                    label: `${w.firstname} ${w.lastname}`,
                };
                return obj;
            });
            setAllWorkers(mapWorkersArr);
        });
    };

    const getClients = () => {
        axios.get("/api/admin/all-clients", { headers }).then((res) => {
            const { clients } = res.data;
            const mapClientsArr = clients.map((c) => {
                let obj = {
                    value: c.id,
                    label: `${c.firstname} ${c.lastname}`,
                };
                return obj;
            });
            setAllClients(mapClientsArr);
        });
    };

    useEffect(() => {
        getWorkers();
        getClients();
    }, []);

    const handleChange = (language) => (event) => {
        setTemplates({
            ...templates,
            [language]: event.target.value,
        });
    };

    const reset =() =>[
        setTemplates({
            message_heb: "",
            message_en: "",
        }),
        setWorkers([]),
        setClients([]),
        setAllClients([]),
        setAllWorkers([]),
        setType("all"),
        setStatus("all"),
    ]


    return (
        <div id="container">
            <Sidebar />
            <div id="content" className="facebook">
                <div className=" m-0">
                    <div className="titleBox customer-title d-flex justify-content-between flex-column">
                        <div className="d-flex justify-content-between align-items-center mb-4">
                            <h1 className="page-title navyblueColor">{t("admin.sidebar.custom_message")}</h1>
                        </div>
                        {/* <div className="d-flex align-items-center flex-wrap">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("global.date_range")}
                            </div>

                            <div className="d-flex align-items-center flex-wrap">
                                <input
                                    className="form-control my-1"
                                    type="date"
                                    placeholder="From date"
                                    name="from filter"
                                    style={{ width: "fit-content" }}
                                    // value={dateRange.start_date}
                                    // onChange={(e) => {
                                    //     setDateRange({
                                    //         start_date: e.target.value,
                                    //         end_date: dateRange.end_date,
                                    //     });
                                    // }}
                                />
                                <div className="mx-2">{t("global.to")}</div>
                                <input
                                    className="form-control my-1"
                                    type="date"
                                    placeholder="To date"
                                    name="to filter"
                                    style={{ width: "fit-content" }}
                                    // value={dateRange.end_date}
                                    // onChange={(e) => {
                                    //     setDateRange({
                                    //         start_date: dateRange.start_date,
                                    //         end_date: e.target.value,
                                    //     });
                                    // }}
                                />
                            </div>
                        </div> */}

                        <div className="d-flex align-items-center flex-wrap">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("global.Type")}
                            </div>
                            <div className="d-flex align-items-center flex-wrap">
                                <Filter_Buttons
                                    text={t("admin.global.leads")}
                                    className="px-3 mr-1"
                                    value="leads"
                                    onClick={() => {
                                        setType("leads");
                                    }}
                                    selectedFilter={type}
                                />
                                <Filter_Buttons
                                    text={t("admin.global.clients")}
                                    className="px-3 mr-1"
                                    value="clients"
                                    onClick={() => {
                                        setType("clients");
                                    }}
                                    selectedFilter={type}
                                />
                                <Filter_Buttons
                                    text={t("admin.global.Workers")}
                                    className="px-3 mr-1"
                                    value="workers"
                                    onClick={() => {
                                        setType("workers");
                                    }}
                                    selectedFilter={type}
                                />
                            </div>
                        </div>
                        {
                            type === "leads" && (
                                <div className=" mb-2 d-flex align-items-center mt-2">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.status")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <FilterButtons
                                            text={t("admin.leads.All")}
                                            className="px-3 mr-1"
                                            selectedFilter={status}
                                            setselectedFilter={setStatus}
                                        />
                                        {leadStatuses.map((_status, _index) => {
                                            return (
                                                <FilterButtons
                                                    text={_status}
                                                    className="px-3 mr-1"
                                                    key={_index}
                                                    selectedFilter={status}
                                                    setselectedFilter={setStatus}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            )
                        }

                        {
                            type === "clients" && (
                                <div className=" mb-2 d-flex align-items-center mt-2">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.status")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <FilterButtons
                                            text={t("admin.leads.All")}
                                            className="px-3 mr-1"
                                            selectedFilter={status}
                                            setselectedFilter={setStatus}
                                        />
                                        {clientStatuses.map((_status, _index) => {
                                            return (
                                                <FilterButtons
                                                    text={_status}
                                                    className="px-3 mr-1"
                                                    key={_index}
                                                    selectedFilter={status}
                                                    setselectedFilter={setStatus}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            )
                        }

                        {
                            (type == "leads" || type == "clients") && (
                                <div className=" mb-2 d-flex align-items-center mt-2">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.client_includes")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <Select
                                            value={clients}
                                            name="clients"
                                            isMulti
                                            options={allClients}
                                            className="basic-multi-single skyBorder"
                                            isClearable={true}
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                            )}
                                            classNamePrefix="select"
                                            onChange={(newValue) =>
                                                setClients(newValue)
                                            }
                                        />
                                    </div>
                                </div>
                            )
                        }

                        {
                            type === "workers" && (
                                <>
                                    <div className=" mb-2 d-flex align-items-center mt-2">
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.status")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <FilterButtons
                                                text={t("admin.leads.All")}
                                                className="px-3 mr-1"
                                                selectedFilter={status}
                                                setselectedFilter={setStatus}
                                            />
                                            {workerStatuses.map((_status, _index) => {
                                                return (
                                                    <FilterButtons
                                                        text={_status}
                                                        className="px-3 mr-1"
                                                        key={_index}
                                                        selectedFilter={status}
                                                        setselectedFilter={setStatus}
                                                    />
                                                );
                                            })}
                                        </div>
                                    </div>
                                    <div className=" mb-2 d-flex align-items-center mt-2">
                                        <div
                                            className=" mr-3"
                                            style={{ fontWeight: "bold" }}
                                        >
                                            {t("admin.global.worker_includes")}
                                        </div>
                                        <div className="d-flex align-items-center flex-wrap">
                                            <Select
                                                value={workers}
                                                name="workers"
                                                isMulti
                                                options={allWorkers}
                                                className="basic-multi-single skyBorder"
                                                isClearable={true}
                                                placeholder={t(
                                                    "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                                )}
                                                classNamePrefix="select"
                                                onChange={(newValue) =>
                                                    setWorkers(newValue)
                                                }
                                            />
                                        </div>
                                    </div>
                                </>
                            )
                        }




                    </div>

                    <div className="dashBox" style={{ backgroundColor: "inherit", border: "none" }}>
                        <form onSubmit={handleSubmit} className={`d-flex ${show ? 'flex-wrap-reverse' : 'nowrap'}`}>
                            <div className="mt-3 mr-3 flex-grow-1 me-4 w-100">
                                <div className="form-group">
                                    <label htmlFor="hebrew">Hebrew</label>
                                    <textarea
                                        id="message_heb"
                                        className="form-control"
                                        maxLength={1000}
                                        rows="5"
                                        value={templates.message_heb}
                                        onChange={handleChange('message_heb')}
                                    />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="english">English</label>
                                    <textarea
                                        id="message_en"
                                        className="form-control"
                                        maxLength={1000}
                                        rows="5"
                                        value={templates.message_en}
                                        onChange={handleChange('message_en')}
                                    />
                                </div>
                                <div className='d-flex align-items-center justify-content-end'>
                                    <button type="button" onClick={() => reset()} className="mt-3 mx-2 btn btn-primary">{t("modal.reset")}</button>
                                    <button type="submit" className="mt-3 btn btn-primary">{t("global.send")}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}


const Filter_Buttons = ({ text, className, selectedFilter, onClick, value }) => (
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === value
                ? { background: "white" }
                : {
                    background: "#2c3f51",
                    color: "white",
                }
        }
        onClick={() => {
            onClick?.();
        }}
    >
        {text}
    </button>
);

export default CustomMessage