import React from "react";
import CheckBox from "./inputElements/CheckBox";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import TextField from "./inputElements/TextField";
import { useTranslation } from "react-i18next";
export const employerInitial = {
    firstName: "",
    address: "",
    fileNumber: "",
    MonthlyIncome: "",
    Tax: "",
    incomeType: "",
    payslip: null,
};
const TaxCoordination = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) => {
    const { t } = useTranslation();

    return (
        <div>
            <h2>{t("form101.taxCordination")}</h2>
            <CheckBox
                name={"TaxCoordination.hasTaxCoordination"}
                label="Attachment of tax coordination"
                checked={
                    values.TaxCoordination &&
                    values.TaxCoordination.hasTaxCoordination
                }
                onChange={handleChange}
                onBlur={handleBlur}
                error={
                    touched.TaxCoordination &&
                    errors.TaxCoordination &&
                    touched.TaxCoordination.hasTaxCoordination &&
                    errors.TaxCoordination.hasTaxCoordination
                        ? errors.TaxCoordination.hasTaxCoordination
                        : ""
                }
            />
            {values.TaxCoordination && (
                <>
                    {values.TaxCoordination.hasTaxCoordination && (
                        <RadioButtonGroup
                            name="TaxCoordination.requestReason"
                            label="Do you have other incomes?"
                            options={[
                                {
                                    label: "The assessing officer has approved tax coordination by the attached confirmation.",
                                    value: "reason1",
                                },
                                {
                                    label: "I have additional incomes from salary as specified below.",
                                    value: "reason2",
                                },
                                {
                                    label: "The assessing officer has approved tax coordination by the attached confirmation..",
                                    value: "reason3",
                                },
                            ]}
                            value={values.TaxCoordination.requestReason}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                errors.TaxCoordination &&
                                touched.TaxCoordination &&
                                errors.TaxCoordination &&
                                touched.TaxCoordination.requestReason &&
                                errors.TaxCoordination.requestReason
                                    ? errors.TaxCoordination.requestReason
                                    : ""
                            }
                            required
                        />
                    )}
                    {values.TaxCoordination.requestReason === "reason1" && (
                        <div>
                            <label htmlFor="TaxCoordination.requestReason1Certificate">
                                Proofs for lack of previous incomes
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxCoordination.requestReason1Certificate"
                                id="exm5disabledCirtificate"
                                accept="image/*"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxCoordination.requestReason1Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxCoordination &&
                                errors.TaxCoordination &&
                                touched.TaxCoordination
                                    .requestReason1Certificate &&
                                errors.TaxCoordination
                                    .requestReason1Certificate && (
                                    <p className="text-danger">
                                        {
                                            errors.TaxCoordination
                                                .requestReason1Certificate
                                        }
                                    </p>
                                )}
                        </div>
                    )}
                    {values.TaxCoordination.requestReason === "reason3" && (
                        <div>
                            <label htmlFor="TaxCoordination.requestReason3Certificate">
                                Tax coordination certificate from the assessing
                                officer
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxCoordination.requestReason3Certificate"
                                id="exm5disabledCirtificate"
                                accept="image/*"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxCoordination.requestReason3Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxCoordination &&
                                errors.TaxCoordination &&
                                touched.TaxCoordination
                                    .requestReason3Certificate &&
                                errors.TaxCoordination
                                    .requestReason3Certificate && (
                                    <p className="text-danger">
                                        {
                                            errors.TaxCoordination
                                                .requestReason3Certificate
                                        }
                                    </p>
                                )}
                        </div>
                    )}
                    {values.TaxCoordination.requestReason === "reason2" && (
                        <div>
                            {values.TaxCoordination.employer.map(
                                (child, index) => (
                                    <div key={index}>
                                        <hr />
                                        Employer/Payer of salary {index +
                                            1}{" "}
                                        <button
                                            type="button"
                                            className="btn btn-sm btn-danger"
                                            onClick={() => {
                                                const newChildren = [
                                                    ...values.TaxCoordination
                                                        .employer,
                                                ];
                                                newChildren.splice(index, 1);
                                                setFieldValue(
                                                    "TaxCoordination.employer",
                                                    newChildren
                                                );
                                            }}
                                        >
                                            -
                                        </button>
                                        <div className="row">
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].firstName`}
                                                    label="First Name"
                                                    value={child.firstName}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .firstName &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .firstName
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].address`}
                                                    label="Address"
                                                    value={child.address}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .address &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .address
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].fileNumber`}
                                                    label="Deductions file number"
                                                    value={child.fileNumber}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .fileNumber &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .fileNumber
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <RadioButtonGroup
                                                    name={`TaxCoordination.employer[${index}].incomeType`}
                                                    label="Type of income"
                                                    options={[
                                                        {
                                                            label: "Work",
                                                            value: "work",
                                                        },
                                                        {
                                                            label: "Allowance",
                                                            value: "allowance",
                                                        },
                                                        {
                                                            label: "Scholarship",
                                                            value: "scholarship",
                                                        },
                                                        {
                                                            label: "Other",
                                                            value: "other",
                                                        },
                                                    ]}
                                                    value={child.incomeType}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .incomeType &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .incomeType
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].MonthlyIncome`}
                                                    label="Monthly income"
                                                    value={child.MonthlyIncome}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .MonthlyIncome &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .MonthlyIncome
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].Tax`}
                                                    label="Tax deducted"
                                                    value={child.Tax}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .Tax &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index].Tax
                                                    }
                                                    required
                                                />
                                            </div>{" "}
                                            <div className="col-4">
                                                <label
                                                    htmlFor={`TaxCoordination.employer[${index}].payslip`}
                                                >
                                                    Photocopy of payslip
                                                </label>
                                                <input
                                                    type="file"
                                                    name={`TaxCoordination.employer[${index}].payslip`}
                                                    id={`TaxCoordination.employer[${index}].payslip`}
                                                    accept="image/*"
                                                    onChange={(e) =>
                                                        setFieldValue(
                                                            `TaxCoordination.employer[${index}].payslip`,
                                                            e.target.files[0]
                                                        )
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                                {touched.TaxCoordination
                                                    .employer &&
                                                    touched.TaxCoordination
                                                        .employer[index] &&
                                                    touched.TaxCoordination
                                                        .employer[index]
                                                        .payslip &&
                                                    errors.TaxCoordination
                                                        .employer &&
                                                    errors.TaxCoordination
                                                        .employer[index] && (
                                                        <p>
                                                            {
                                                                errors
                                                                    .TaxCoordination
                                                                    .employer[
                                                                    index
                                                                ].payslip
                                                            }
                                                        </p>
                                                    )}
                                            </div>
                                        </div>
                                    </div>
                                )
                            )}
                            <button
                                type="button"
                                className="btn btn-success save my-3"
                                onClick={() => {
                                    setFieldValue("TaxCoordination.employer", [
                                        ...values.TaxCoordination.employer,
                                        employerInitial,
                                    ]);
                                }}
                            >
                                + ADD EMPLOYER/PAYER OF SALARY
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default TaxCoordination;
