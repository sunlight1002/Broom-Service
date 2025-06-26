// IncomeDetails

import React, { useEffect } from "react";
import CheckBox from "./inputElements/CheckBox";
import DateField from "./inputElements/DateField";
import { useTranslation } from "react-i18next";

const IncomeDetails = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
    handleBubbleToggle,
    activeBubble
}) => {
    const { t } = useTranslation();
    const handleIncomeChange = (event) => {
        const { name, value, checked } = event.target;
        const newValue = checked ? value : "";
        setFieldValue(name, newValue);
    };

    // console.log(values);
    

    return (
        <div className="mt-3">
            <p className="navyblueColor font-24 font-w-500  mb-2">{t("form101.employer_income_details")}</p>

            <div className="row">
                <div className="col">
                    <div style={{ width: "calc(100% - 46px)"}}>
                        <DateField
                            name="DateOfBeginningWork"
                            label={t("form101.do_commencement")}
                            value={values.DateOfBeginningWork}
                            onChange={(e) => {
                                if (e.target.value !== null) {
                                    setFieldValue("DateOfBeginningWork", e.target.value);
                                }
                            }}
                            toggleBubble={handleBubbleToggle}
                            onBlur={handleBlur}
                            error={
                                touched.DateOfBeginningWork && errors.DateOfBeginningWork
                                    ? errors.DateOfBeginningWork
                                    : ""
                            }
                            required
                        />
                        </div>
                        <div className="position-absolute"
                            style={{
                                right: "15px",
                                top: "60%",
                                transform: "translateY(-50%)",
                                cursor: "pointer",
                                color: "#000",
                                backgroundColor: "#fafafa",
                                borderRadius: "25%", 
                                padding: "7px 8px 5px 8px",
                                zIndex: 2
                            }}
                            onClick={() => {handleBubbleToggle("DateOfBeginningWork")}}
                            title="Click for help"
                            >
                            <svg
                                stroke="currentColor"
                                fill="currentColor"
                                strokeWidth="0"
                                viewBox="0 0 1024 1024"
                                height="1.5em"
                                width="1.4em"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path>
                                <path d="M623.6 316.7C593.6 290.4 554 276 512 276s-81.6 14.5-111.6 40.7C369.2 344 352 380.7 352 420v7.6c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V420c0-44.1 43.1-80 96-80s96 35.9 96 80c0 31.1-22 59.6-56.1 72.7-21.2 8.1-39.2 22.3-52.1 40.9-13.1 19-19.9 41.8-19.9 64.9V620c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8v-22.7a48.3 48.3 0 0 1 30.9-44.8c59-22.7 97.1-74.7 97.1-132.5.1-39.3-17.1-76-48.3-103.3zM472 732a40 40 0 1 0 80 0 40 40 0 1 0-80 0z"></path>
                            </svg>
                        </div>

                        {activeBubble === "DateOfBeginningWork" && (
                            <div className="position-absolute speechand up">
                                {t("Enter date when you started working during the current tax year.")}
                            </div>
                        )}
                    </div>
            </div>
            <div className="row mt-2">
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.month_salary")}
                        value="Monthly salary"
                        checked={values.incomeType === "Monthly salary"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>{" "}
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.salary_ap")}
                        value="Salary for additional employment"
                        checked={
                            values.incomeType ===
                            "Salary for additional employment"
                        }
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.partial_salary")}
                        value="Partial salary"
                        checked={values.incomeType === "Partial salary"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
            </div>
            <div className="row">
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.daily_wages")}
                        value="Wage (Daily rate of pay)"
                        checked={
                            values.incomeType === "Wage (Daily rate of pay)"
                        }
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.allowance")}
                        value="Allowance"
                        checked={values.incomeType === "Allowance"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.scholarship")}
                        value="Scholarship"
                        checked={values.incomeType === "Scholarship"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>

            </div>
        </div>
    );
};

export default IncomeDetails;
