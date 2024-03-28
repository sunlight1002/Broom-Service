import React from "react";
import { useTranslation } from "react-i18next";

import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";
export default function Services({ job }) {
    const { t, i18n } = useTranslation();
    const c_lng = i18n.language;
    // const show_shift = ["Full Day", "Morning", "Afternoon", "Evening", "Night"];
    // const getShift = (shifts) => {
    //     let s = shifts.split(",");
    //     let check = "";
    //     let new_shift = "";
    //     show_shift.map((p) => {
    //         if (p == "Afternoon") {
    //             check = "noon";
    //         } else {
    //             check = p;
    //         }
    //         s.map((sh) => {
    //             if (sh.includes(check.toLowerCase())) {
    //                 if (new_shift == "") {
    //                     new_shift = p;
    //                 } else {
    //                     if (!new_shift.includes(p)) {
    //                         new_shift =
    //                             t("global." + new_shift.toLowerCase()) +
    //                             " | " +
    //                             t("global." + p.toLowerCase());
    //                     }
    //                 }
    //             }
    //         });
    //     });
    //     if (new_shift == "Full Day") return t("global.fullday");
    //     if (new_shift == "Morning") return t("global.morning");
    //     if (new_shift == "Noon") return t("global.noon");
    //     if (new_shift == "Afternoon") return t("global.afternoon");
    //     if (new_shift == "Evening") return t("global.evening");
    //     return new_shift;
    // };
    let status = job.status;
    if (status == "not-started") {
        status = t("j_status.not-started");
    }
    if (status == "progress") {
        status = t("j_status.progress");
    }
    if (status == "completed") {
        status = t("j_status.completed");
    }
    if (status == "scheduled") {
        status = t("j_status.scheduled");
    }
    if (status == "unscheduled") {
        status = t("j_status.unscheduled");
    }
    if (status == "re-scheduled") {
        status = t("j_status.re-scheduled");
    }
    if (status == "cancel") {
        status = t("j_status.cancel");
    }

    const service = job.jobservice;

    return (
        <>
            <h2 className="text-custom">Service Details</h2>
            <div className="dashBox p-4 mb-3">
                {service && (
                    <form>
                        <div className="row">
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.service")}
                                    </label>
                                    <p>
                                        {c_lng == "en"
                                            ? service.name
                                            : service.heb_name}
                                    </p>
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.c_time")}
                                    </label>
                                    <p>
                                        {service.duration_minutes
                                            ? convertMinsToDecimalHrs(
                                                  service.duration_minutes
                                              )
                                            : "NA"}{" "}
                                        {t("client.jobs.view.hour_s")}
                                    </p>
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.shift")}
                                    </label>
                                    <p>{job.shifts}</p>
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.job_status")}
                                    </label>
                                    <p>{status}</p>
                                    {job.status == "cancel" &&
                                        ` (with cancellation fees of ${job.cancellation_fee_amount} ILS)`}
                                </div>
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}
