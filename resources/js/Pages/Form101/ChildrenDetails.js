//ChildrenDetails

import React from "react";
import TextField from "./inputElements/TextField";
import DateField from "./inputElements/DateField";
import CheckBox from "./inputElements/CheckBox";
import { useTranslation } from "react-i18next";
import { FaRegTrashCan } from "react-icons/fa6";


export const childrenInitial = {
    firstName: "",
    IdNumber: "",
    Dob: "",
    inCustody: false,
    haveChildAllowance: false,
};
const ChildrenDetails = ({
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

    return (
        <div>
            <p className="navyblueColor font-24 font-w-500  mb-2">{t("form101.children_headline")}</p>

            {values.children &&
                values.children.map((child, index) => (
                    <div key={index}>
                        <hr />
                        {t("form101.child")} {index + 1}{"  "}
                        <button
                            type="button"
                            className="btn btn-sm ml-2 mb-2 action-btn"
                            onClick={() => {
                                const newChildren = [...values.children];
                                newChildren.splice(index, 1);
                                setFieldValue("children", newChildren);
                            }}
                        >
                            <FaRegTrashCan />
                        </button>
                        <div className="row">
                            <div className="col">
                                <TextField
                                    name={`children[${index}].firstName`}
                                    label={t("form101.label_firstName")}
                                    value={child.firstName}
                                    onChange={handleChange}
                                    toggleBubble={handleBubbleToggle}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].firstName &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].firstName
                                    }
                                    required
                                />
                                {activeBubble === `children[${index}].firstName` && (
                                    <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                        <div className="speech up">
                                            {t("Enter your full first name.")}
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="col">
                                <TextField
                                    name={`children[${index}].IdNumber`}
                                    label={t("form101.id_num")}
                                    value={child.IdNumber}
                                    onChange={handleChange}
                                    toggleBubble={handleBubbleToggle}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].IdNumber &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].IdNumber
                                    }
                                    required
                                />
                                {activeBubble === `children[${index}].IdNumber` && (
                                    <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                        <div className="speech up">
                                            {t("If applicable, enter your ID number.")}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-md-6">
                                <div style={{ width: "calc(100% - 46px)"}}>
                                    <DateField
                                        name={`children[${index}].Dob`}
                                        label={t("form101.dob")}
                                        value={child.Dob}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.children &&
                                            touched.children[index] &&
                                            touched.children[index].Dob &&
                                            errors.children &&
                                            errors.children[index] &&
                                            errors.children[index].Dob
                                        }
                                        required={true}
                                    />
                                </div>
                                <div
                                    className="position-absolute"
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
                                    onClick={() => {handleBubbleToggle(`children[${index}].Dob`)}}
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

                                {activeBubble === `children[${index}].Dob` && (
                                    <div className="position-absolute speechand up">
                                        {t("Enter your date of birth in MM/DD/YYYY format.")}
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="row">
                            <div className="ml-3">
                                <CheckBox
                                    name={`children[${index}].inCustody`}
                                    label={t("form101.child_custody")}
                                    value={child.inCustody}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    checked={child.inCustody}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].inCustody &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].inCustody
                                    }
                                />
                            </div>
                            <div className="ml-4">
                                <CheckBox
                                    name={`children[${index}].haveChildAllowance`}
                                    label={t("form101.child_insurance")}
                                    value={child.haveChildAllowance}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    checked={child.haveChildAllowance}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index]
                                            .haveChildAllowance &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index]
                                            .haveChildAllowance
                                    }
                                />
                            </div>
                        </div>
                    </div>
                ))}
            <button
                type="button"
                className="btn button add mb-3 mt-3 action-btn"
                onClick={() => {
                    setFieldValue("children", [
                        ...values.children,
                        childrenInitial,
                    ]);
                }}
            >
                {t("form101.button_addChild")}
            </button>
        </div>
    );
};

export default ChildrenDetails;
