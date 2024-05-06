import axios from "axios";
import React, { useState, useEffect } from "react";
import Moment from "moment";
import { useTranslation } from "react-i18next";

export default function Forms() {
    const [forms, setForms] = useState([]);

    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const getForms = () => {
        axios.get(`/api/forms`, { headers }).then((res) => {
            if (res.data && res.data) {
                setForms(res.data.forms);
            }
        });
    };

    useEffect(() => {
        getForms();
    }, []);

    return (
        <div
            className="tab-pane fade active show"
            id="tab-forms"
            role="tabpanel"
            aria-labelledby="tab-forms-tab"
        >
            <div className="col-md-12">
                {forms.map((d, i) => (
                    <div
                        key={i}
                        className="card card-widget widget-user-2"
                        style={{ boxShadow: "none" }}
                    >
                        <div className="card-comments cardforResponsive"></div>
                        <div
                            className="card-comment p-3"
                            style={{
                                backgroundColor: "rgba(0,0,0,.05)",
                                borderRadius: "5px",
                            }}
                        >
                            <div className="row">
                                <div className="col-sm-3 col-3">
                                    <span
                                        className="noteDate"
                                        style={{ fontWeight: "600" }}
                                    >
                                        {d.submitted_at
                                            ? Moment(d.submitted_at).format(
                                                  "DD-MM-Y"
                                              )
                                            : "NA"}
                                    </span>
                                </div>
                                <div className="col-sm-3 col-3">
                                    <p
                                        style={{
                                            fontSize: "16px",
                                            fontWeight: "600",
                                        }}
                                    >
                                        {d.type}
                                    </p>
                                </div>
                                <div className="col-sm-4 col-4">
                                    {d.pdf_name && (
                                        <a
                                            href={`/storage/signed-docs/${d.pdf_name}`}
                                            target={"_blank"}
                                            download={d.type}
                                        >
                                            <span className="btn-default">
                                                <i className="fa fa-download"></i>
                                            </span>
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
